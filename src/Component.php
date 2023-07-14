<?php

namespace Axm\Raxm;

use Axm;
use Axm\Views\View;
use Axm\Http\Request;
use Axm\Http\Response;
use Axm\Exception\AxmException;
use Axm\Raxm\ComponentCheckSum;
use Axm\Raxm\ComponentProperties;
use Axm\Raxm\LifecycleManager;
use App\Controllers\BaseController;


abstract class Component extends BaseController
{
    protected bool $shouldSkipRendering = false;
    protected ?array $serverMemo  = null;
    protected ?array $fingerprint = null;
    protected ?array $updates = null;
    protected ?string $preRenderedView;
    protected ?array $publicProperties;

    public ?string $id;
    public ?array $effects = [];

    protected $id_p;
    protected $component;
    protected $type;
    protected $method;
    protected $params;
    protected ?Request $request;
    protected ?Response $response;


    public function __construct()
    {
        $app = Axm::app();
        $this->request  = $app->request  ?? null;
        $this->response = $app->response ?? null;
    }


    public function run()
    {
        $this->extractDataRequest($this->request);
        $this->hydrateFromServerMemo();
        $this->hydratePayload();
        $this->dispatchEvents();
        $this->prepareAndSendJsonResponse();
    }


    private function extractDataRequest(Request $request): void
    {
        $this->serverMemo  = $request->serverMemo ?? [];
        $this->updates     = $request->updates ?? [];
        $this->fingerprint = $request->fingerprint ?? [];
        [$this->id, $this->component] = [$this->fingerprint['id'], ucfirst($this->fingerprint['name'])];
    }


    private function hydrateFromServerMemo(): void
    {
        $this->mount($this->serverMemo['data']);
    }


    private function hydratePayload()
    {
        $payloads = $this->updates ?? [];

        foreach ($payloads as $item) {
            $payload      = $item['payload'];
            $this->type   = $item['type'];
            $this->id_p   = $payload['id'];
            $this->method = $payload['method'] ?? null;
            $this->params = $payload['params'] ?? null;
        }
    }


    private function dispatchEvents()
    {
        switch ($this->type) {
            case 'callMethod':
                return $this->callMethod($this->method, $this->params);

            case 'syncInput':
                return $this->syncInputData();

            case 'fireEvent':
                return $this->fireEvent();

            default:
                throw new AxmException('Tipo de evento desconocido: ' . $this->type);
        }
    }


    private function callMethod($method, $params = [])
    {
        $method = trim($method);
        $prop   = array_shift($params);

        switch ($method) {
            case '$sync':
            case '$set':
                return $this->syncInput($prop, $params);

            case '$toggle':
                $currentValue = $this->{$prop};  // Added line to store current value of the property 
                return $this->syncInput($prop, !$currentValue);

            case '$refresh':
                return;
        }

        if (!(ComponentProperties::methodIsPublic($this, $method))) {  // Added braces for readability 
            throw new AxmException(Axm::t('Raxm', "Unable to set component data. Public method {method()} not found on component: {component}", ['{method()}' => $method, '{component}' => $this->component]));  // Added braces for readability 
        }

        return $this->$method(...$params);
    }


    private function syncInputData(): void
    {
        foreach ($this->updates as $update) {

            $name  = $update['payload']['name']  ?? null;
            $value = $update['payload']['value'] ?? null;

            $this->syncInput($name, $value);
        }
    }


    private function syncInput(string $propertyName, $value): void
    {
        // Check if the property exists
        if (!property_exists($this, $propertyName)) {
            throw new AxmException(Axm::t('Raxm', "Unable to set component data. The public property \${property} was not found on the component: {component}", ['{property}' => $propertyName, '{component}' => $this->component]));
        }

        $this->mount([$propertyName => $value]);
        // Generate a hash for the ID if it is not set
        $this->id = $this->id ?? $this->generateId();
    }


    private function generateId(): string
    {
        return hash('sha256', randomId());
    }


    protected function mount($params = [])
    {
        $this->publicProperties = ComponentProperties::getPublicProperties($this);
        foreach ($params as $property => $value) {
            if (array_key_exists($property, $this->publicProperties) && (is_scalar($value) || is_null($value))) {
                $this->{$property} = $value;
            }
        }

        return $this;
    }


    public function initialInstance($id = null)
    {
        // Generate a random ID if one is not already set.
        $this->id = $id ?? bin2hex(random_bytes(20));

        // Prepare the response that will be sent to the client.
        $this->prepareResponse();

        // Return the response to the client.
        return show($this->html());
    }



    public function html()
    {
        return $this->effects['html'] ?? null;
    }


    public function embedThyselfInHtml()
    {
        if (!$html = $this->renderToView()) return;
        $this->effects['html'] = (new HtmlRootTagAttributeAdder)($html, [
            'initial-data' => $this->toArrayWithoutHtml()
        ]);
    }


    public function embedIdInHtml()
    {
        if (!$html = $this->effects['html'] ?? null) return;
        $this->effects['html'] = (new HtmlRootTagAttributeAdder)($html, [
            'id' => $this->id,
        ]);
    }


    protected function toArrayWithoutHtml()
    {
        $fingerprint = $this->fingerprint ?? LifecycleManager::initialFingerprint();
        $effects     = array_diff_key($this->effects, ['html' => null]) ?: LifecycleManager::initialEffects();
        $serverMemo  = $this->serverMemo ?? LifecycleManager::createDataServerMemo();

        return compact('fingerprint', 'effects', 'serverMemo');
    }


    private function renderToView()
    {
        $params = ComponentProperties::getPublicProperties($this);
        $view   = method_exists($this, 'render')
            ? $this->callRender()
            : View::getView('Raxm' . $this->fingerprint['name'], $params);

        if ($view instanceof View)
            throw new AxmException('"render" method on [' . $this->component . '] must return instance of [' . View::class . ']');

        if (!is_string($view) || empty($view)) throw new AxmException('La vista está vacía o no es una cadena de caracteres.');
        return $this->preRenderedView = $view;
    }


    protected function callRender()
    {
        $this->publicProperties = View::$tempData = ComponentProperties::getPublicProperties($this);
        return $this->render();
    }


    protected function output()
    {
        if ($this->shouldSkipRendering) return null;
        return $this->renderToView();
    }


    protected function prepareResponse(): array
    {
        $this->embedThyselfInHtml();
        $this->embedIdInHtml();

        return [
            'effects' => $this->effects(),
            'serverMemo' => [
                'htmlHash' => randomId(8),
                'data'     => $this->dataResponse(),
                'checksum' => $this->checkSumAndGenerate(
                    $this->serverMemo['checksum'] ?? '',
                    $this->fingerprint ?? [],
                    $this->serverMemo  ?? []
                )
            ],
        ];
    }


    protected function effects()
    {
        return [
            'html'  => $this->html(),
            'dirty' => $this->getChangedData()
        ];
    }


    protected function checkSumAndGenerate($checksum, $fingerprint, $memo)
    {
        if (ComponentCheckSum::check($checksum, $fingerprint, $memo))
            throw new AxmException("Raxm encountered corrupt data when trying to hydrate the $this->component component. \n" . "Ensure that the [name, id, data] of the Raxm component wasn't tampered with between requests.");

        return ComponentCheckSum::generate($fingerprint, $memo);
    }


    protected function getChangedData()
    {
        $changedData = [];
        foreach ($this->serverMemo['data'] ?? [] as $key => $value) {
            if (isset($this->{$key}) && $this->{$key} != $value) {
                $changedData[] = $key;
            }
            return $changedData;
        }
    }


    protected function dataResponse()
    {
        return array_filter($this->publicProperties, function ($key) {
            return property_exists($this, $key);
        }, ARRAY_FILTER_USE_KEY);
    }


    protected function prepareAndSendJsonResponse()
    {
        $response = $this->prepareResponse();
        return $this->response->toJson($response);
    }


    public function __call($method, $params)
    {
        $methodReserved = ['mount', 'hydrate', 'dehydrate', 'updating', 'updated'];
        if (in_array($method, $methodReserved)) {
            throw new AxmException(Axm::t('Raxm', 'This methods is reserved for Raxm: {methods}', ['{methods}' => implode(',', $methodReserved)]));
        }

        if (is_array($params)) {
            $params = '(' . implode(',', $params) . ')';
        }

        throw new AxmException(Axm::t('Raxm', 'El método "{class}::{method}' . $params . '" no existe.', ['{class}' => static::class, '{method}' => $method]));
    }
}
