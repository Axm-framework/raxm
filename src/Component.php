<?php

namespace Axm\Raxm;

use Axm;
use Axm\Views\View;
use Axm\Exception\AxmException;
use Axm\Http\Request;
use Axm\Http\Response;
use Axm\Raxm\ComponentCheckSum;
use Axm\Raxm\ComponentProperties;
use Axm\Raxm\LifecycleManager;
use App\Controllers\BaseController;
use Axm\Raxm\ReceivesEvents;
use Axm\Raxm\Support\InteractsWithProperties;
use Axm\Raxm\Support\HandlesActions;
use Axm\Raxm\Support\ValidatesInput;

/**
 * Abstract base class for Raxm components.
 *
 * This class serves as the base class for all Raxm components, providing common functionality and methods.
 * Raxm components are used to build dynamic web interfaces with real-time updates and interactions.
 */
abstract class Component extends BaseController
{
    use ReceivesEvents;
    use InteractsWithProperties;
    use HandlesActions;
    use ValidatesInput;

    protected ?Request  $request;
    protected ?Response $response;
    protected bool $shouldSkipRendering = false;

    protected ?array  $serverMemo  = null;
    protected ?array  $fingerprint = null;
    protected ?array  $updates = null;
    protected ?array  $publicProperties;
    protected ?string $preRenderedView;
    protected bool $ifActionIsRedirect  = false;
    protected bool $ifActionIsNavigate  = false;



    public ?string $id;
    public ?array $effects = [];

    protected $id_p;
    protected $component;
    protected $type;
    protected $method;
    protected $params;
    protected $payload;

    protected $eventQueue    = [];
    protected $dispatchQueue = [];
    protected $listeners     = [];
    protected $queryString   = [];

    protected $rules = [];

    public $tmpfile;

    /**
     * Constructor for the Raxm Component.
     */
    public function __construct()
    {
        $app = Axm::app();
        $this->request  = $app->request  ?? null;
        $this->response = $app->response ?? null;
    }

    /**
     * Run the Raxm component.
     *
     * This method is responsible for executing the Raxm component's logic.
     * It performs tasks such as checking if the request is a Raxm request,
     * extracting data from the request, hydrating the component's state,
     * dispatching events, and preparing and sending a JSON response.
     */
    public function run()
    {
        $this->isRaxmRequest();
        $this->extractDataRequest($this->request);
        $this->hydrateFromServerMemo();
        $this->hydratePayload();
        $this->dispatchEvents();
        $this->prepareAndSendJsonResponse();
    }

    /**
     * Check if the request is a Raxm request.
     *
     * This method checks if the current HTTP request is a valid Raxm request.
     * It verifies the 'x-axm' header to ensure that the request is a Raxm request.
     * If the check fails, it throws an exception.
     */
    private function isRaxmRequest()
    {
        if ($this->request->getHeader('x-axm') != true) {
            throw new AxmException(Axm::t('Raxm', 'This request is not a Raxm request'));
        }
    }

    /**
     * Extract data from the request.
     *
     * This method extracts data from the HTTP request, including server memo,
     * updates, and fingerprint data. It sets the component's ID and name based on the fingerprint.
     * @param Request $request The HTTP request object.
     */
    private function extractDataRequest(Request $request): void
    {
        $this->serverMemo  = $request->serverMemo  ?? [];
        $this->updates     = $request->updates     ?? [];
        $this->fingerprint = $request->fingerprint ?? [];

        [$this->id, $this->component] = [
            $this->fingerprint['id'], $this->fingerprint['name']
        ];
    }

    /**
     * Hydrate the component's state from the server memo.
     * This method hydrates the component's state using data
     * from the server memo.
     */
    private function hydrateFromServerMemo(): void
    {
        $this->mount($this->serverMemo['data']);
    }

    /**
     * Hydrate the component's payload data.
     * This method hydrates the component's payload data based on 
     * updates received from the client.
     */
    private function hydratePayload()
    {
        $payloads = $this->updates ?? [];

        foreach ($payloads as $item) {
            $payload = $this->payload = $item['payload'];

            $this->type   = $item['type'];
            $this->id_p   = $payload['id'];
            $this->method = $payload['method'] ?? null;
            $this->params = $payload['params'] ?? null;
        }
    }

    /**
     * Dispatch events based on the payload type.
     * This method dispatches events based on the type of payload 
     * received from the client.
     */
    private function dispatchEvents()
    {
        switch ($this->type) {
            case 'callMethod':
                return $this->callMethod($this->method, $this->params);

            case 'syncInput':
                return $this->syncInputData();

            case 'fireEvent':
                return $this->fireEvent($this->method, $this->params, $this->id_p);

            case 'navigate':
                return $this->navigate($this->payload);

            default:
                throw new AxmException('Unknown event type: ' . $this->type);
        }
    }

    /**
     * Call a component method based on the payload.
     *
     * This method calls a component method based on the payload received from the client.
     * @param mixed $method The method to call.
     * @param array $params The method parameters.
     * @return mixed The result of the method call.
     */
    protected function callMethod($method, $params = [])
    {
        // Trimming the method name.
        $method = trim($method);
        $prop = array_shift($params);

        switch ($method) {
            case '$sync':
            case '$set':
                return $this->syncInput($prop, $params);

            case '$toggle':
                $currentValue = $this->{$prop};  // Added line to store the current value of the property.
                return $this->syncInput($prop, !$currentValue);

            case '$refresh':
                return;
        }

        if ($method === 'render') return false;

        if (!method_exists($this, $method)) {
            if ($method === 'startUpload') {
                throw new AxmException("Cannot handle file upload without [Axm\Raxm\Support\WithFileUploads] trait on the [{$this->component}] component class.");
            }

            throw new AxmException("Unable to call component method. Public method [$method] not found on component: [{$this->component}]");
        }

        if (!(ComponentProperties::methodIsPublic($this, $method))) {
            throw new AxmException(Axm::t('Raxm', 'Unable to set component data. Public method %s not found on component: %s', [$method, $this->component]));  // Added braces for readability.
        }

        return $this->$method(...$params);
    }

    /**
     * Sync input data based on updates.
     * This method syncs input data based on updates received from the client.
     */
    private function syncInputData(): void
    {
        foreach ($this->updates as $update) {
            $name  = $update['payload']['name']  ?? null;
            $value = $update['payload']['value'] ?? null;

            $this->syncInput($name, $value);
        }
    }

    /**
     * Generate a random ID.
     *
     * This method generates a random ID using the `randomId` function.
     * @return string The generated random ID.
     */
    private function generateId(): string
    {
        return hash('sha256', randomId());
    }

    /**
     * Initialize the Raxm component.
     *
     * This method initializes the Raxm component, setting its ID and preparing the response for the client.
     * @param string|null $id The component ID (optional).
     * @return mixed The response to send to the client.
     */
    public function initialInstance($id = null)
    {
        // Generate a random ID if one is not already set.
        $this->id = $id ?? bin2hex(random_bytes(20));

        // Prepare the response that will be sent to the client.
        $this->prepareResponse();

        // Return the response to the client.
        return show($this->html());
    }

    /**
     * Get the HTML representation of the component.
     *
     * This method returns the HTML representation of the component, which is stored in the 'effects' array.
     * @return string|null The HTML representation of the component.
     */
    public function html()
    {
        return $this->effects['html'] ?? null;
    }

    /**
     * Embed the component's data in the HTML representation.
     *
     * This method embeds the component's data in the HTML representation 
     * by adding attributes to the HTML root tag.
     */
    public function embedThyselfInHtml()
    {
        if (!$html = $this->renderToView()) return;
        $this->effects['html'] = (new HtmlRootTagAttributeAdder)($html, [
            'initial-data' => $this->toArrayWithoutHtml()
        ]);
    }

    /**
     * Embed the component's ID in the HTML representation.
     *
     * This method embeds the component's ID in the HTML representation by adding an 'id' 
     * attribute to the HTML root tag.
     */
    public function embedIdInHtml()
    {
        if (!$html = $this->effects['html'] ?? null) return;
        $this->effects['html'] = (new HtmlRootTagAttributeAdder)($html, [
            'id' => $this->id,
        ]);
    }

    /**
     * Get an array of component data without HTML.
     *
     * This method returns an array of component data without the HTML representation.
     * @return array The component data without HTML.
     */
    protected function toArrayWithoutHtml()
    {
        $fingerprint = $this->fingerprint ?? LifecycleManager::initialFingerprint();
        $effects     = array_diff_key($this->effects, ['html' => null]) ?: LifecycleManager::initialEffects();
        $serverMemo  = $this->serverMemo ?? LifecycleManager::createDataServerMemo();

        return compact('fingerprint', 'effects', 'serverMemo');
    }

    /**
     * Render the component to a view.
     *
     * This method renders the component to a view, which is either returned by 
     * the 'render' method or a default view.
     * @return string|null The rendered view or null if not found.
     */
    private function renderToView()
    {
        app('raxm')->includeHelpers();

        $view = $this->getView();
        if ($view instanceof View) {
            throw new AxmException('"render" method on [' . $this->component . '] must return an instance of [' . View::class . ']');
        }

        return $this->preRenderedView = $view;
    }

    /**
     * Call the component's 'render' method.
     *
     * This method calls the 'render' method of the component or falls back to a default 
     * view if the 'render' method is not defined.
     * @return string|null The rendered view or null if not found.
     */
    protected function callRender()
    {
        $mergePublicProperties  = View::$tempData = $this->getPublicProperties($this);
        $this->publicProperties = $mergePublicProperties;

        return $this->render();
    }

    /**
     * Get the view for the component.
     *
     * This method retrieves the view for the component, either by calling 
     * the 'render' method or using a default view name.
     * @return string|null The view for the component.
     */
    private function getView(): ?string
    {
        $view = method_exists($this, 'render')
            ? $this->callRender() : view('raxm.' . $this->getComponentName());

        return $view;
    }

    /**
     * Get the name of the component.
     *
     * This method returns the name of the component using the 'componentName' 
     * method from RaxmManager.
     * @return string The name of the component.
     */
    protected function getComponentName()
    {
        return RaxmManager::componentName();
    }

    /**
     * Get the query string data for the component.
     *
     * This method retrieves the query string data for the component, considering 
     * class traits and component-specific query strings.
     * @return array The query string data.
     */
    public function getQueryString()
    {
        $componentQueryString = method_exists($this, 'queryString')
            ? $this->queryString()
            : $this->queryString;

        $class  = static::class;
        $traits = class_uses_recursive($class);
        $queryStringData = [];

        foreach ($traits as $trait) {
            $member = 'queryString' . class_basename($trait);

            if (method_exists($class, $member)) {
                $queryStringData = array_merge($queryStringData, $this->{$member}());
            }

            if (property_exists($class, $member)) {
                $queryStringData = array_merge($queryStringData, $this->{$member});
            }
        }

        $queryStringData = array_merge($queryStringData, $componentQueryString);

        return $queryStringData;
    }

    /**
     * Mounts data to the component's public properties.
     *
     * This method populates the component's public properties with data provided in the $params array,
     * ensuring that the data is only assigned to properties that are declared as public and have compatible types.
     * @param array $params An associative array containing property-value pairs to be assigned to the component.
     * @return $this The current instance of the component after mounting the data.
     */
    protected function mount($params = [])
    {
        // Get the list of public properties for the component.
        $this->publicProperties = ComponentProperties::getPublicProperties($this);

        foreach ($params as $property => $value) {

            if (isset($this->publicProperties[$property]) && (is_array($value) || is_scalar($value) || is_null($value))) {
                // Assign the value to the property.
                $this->{$property} = $value;
            }
        }

        return $this;
    }

    /**
     * Get the public properties of the component.
     *
     * This method retrieves the public properties of the component using 
     * the 'getPublicProperties' method from ComponentProperties.
     * @return array The public properties of the component.
     */
    private function getPublicProperties()
    {
        return ComponentProperties::getPublicProperties($this);
    }

    /**
     * Output the component data.
     *
     * This method outputs the component data, which is either the rendered 
     * view or null if rendering is skipped.
     * @return string|null The component data.
     */
    protected function output()
    {
        if ($this->shouldSkipRendering) return null;
        return $this->renderToView();
    }

    /**
     * Prepare the response data for the client.
     *
     * This method prepares the response data that will be sent to the client, 
     * including effects, server memo, and checksum.
     * @return array The prepared response data.
     */
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

    /**
     * Get the effects data for the client.
     *
     * This method retrieves the effects data, including HTML, dirty data, events,
     * and listeners, to be sent to the client.
     * @return array The effects data.
     */
    protected function effects()
    {
        $effects = [
            'html'  => $this->html(),
            'dirty' => $this->getChangedData(),
            'emits' => $this->getEventQueue(),
            'listeners'  => $this->getEventsBeingListenedFor(),
            'dispatches' => $this->getDispatchQueue(),
        ];

        // Verificamos si $this->ifActionIsRedirect es true antes de agregar 'redirect' al array.
        if ($this->ifActionIsRedirect == true) {
            $effects['redirect'] = $this->getRedirectTo();
        }

        if ($this->ifActionIsNavigate == true) {
            $effects['navigate'] = $this->getRedirectTo();
        }


        return $effects;
    }


    /**
     * Add effects data to the component.
     *
     * This method adds additional effects data to the component's effects array.
     * @param string $key The key for the effect data.
     * @param mixed $value The value of the effect data.
     * @return mixed The updated effects array.
     */
    protected function addEffects(string $key, $value)
    {
        return $this->effects[$key] = $value;
    }

    /**
     * Get the URL to redirect to.
     *
     * This method determines the URL to which the client should be redirected
     * based on the 'redirect' method or a default URL.
     * @return string The URL to redirect to.
     */
    protected function getRedirectTo()
    {
        if (!empty($this->payload['value']) && $this->ifActionIsNavigate = true) {

            $url = generateUrl(cleanInput($this->payload['value']));
            $view = (string) file_get_contents($url);
            $name = basename($url);
            $navigate = ['name' => $name, 'url' => $url, 'html' => $view];

            return $navigate;
        }

        if (method_exists($this, 'redirect')) {
            $pathTo = $this->redirect();
            return generateUrl($pathTo);
        }

        return generateUrl('/');
    }

    /**
     * Check and generate the checksum for data integrity.
     *
     * This method checks and generates a checksum to ensure the integrity of the component's data.
     * @param string $checksum The existing checksum.
     * @param array $fingerprint The component's fingerprint data.
     * @param array $memo The server memo data.
     * @return mixed The generated checksum.
     */
    protected function checkSumAndGenerate($checksum, $fingerprint, $memo)
    {
        if (ComponentCheckSum::check($checksum, $fingerprint, $memo))
            throw new AxmException("Raxm encountered corrupt data when trying to hydrate the $this->component component. \n" . "Ensure that the [name, id, data] of the Raxm component wasn't tampered with between requests.");

        return ComponentCheckSum::generate($fingerprint, $memo);
    }

    /**
     * Get the changed data properties.
     *
     * This method retrieves the properties of the component's data that have 
     * changed compared to the server memo.
     * @return array The changed data properties.
     */
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

    /**
     * Get the data to include in the server response.
     *
     * This method retrieves the data to include in the server response, 
     * excluding any properties that are not part of the component's public properties.
     * @return array The data to include in the server response.
     */
    protected function dataResponse()
    {
        return array_filter($this->publicProperties, function ($key) {
            return property_exists($this, $key);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Prepare and send a JSON response to the client.
     * This method prepares the response data and sends it as a JSON response to the client.
     */
    protected function prepareAndSendJsonResponse()
    {
        $response = $this->prepareResponse();

        return $this->response->toJson($response);
    }

    /**
     * Handle dynamic method calls.
     *
     * This method handles dynamic method calls on the component,
     * allowing it to call its methods.
     * @param string $method The method to call.
     * @param array $params The method parameters.
     * @return mixed The result of the method call.
     */
    public function __call($method, $params)
    {
        $reservedMethods = ['hydrate', 'dehydrate', 'updatingñ', 'updated'];
        if (in_array($method, $reservedMethods)) {
            throw new AxmException(Axm::t('Raxm', 'This method is reserved for Raxm [%s]', [implode(', ', $reservedMethods)]));
        }

        $className = static::class;
        if (!method_exists($this, $method)) {
            throw new AxmException(Axm::t('Raxm', 'Method %s does not exist', ["$className::$method()"]));
        }
        return $this->$method(...$params);
    }

    /**
     * Magic method to access properties of the class.
     *
     * @param string $property The name of the property to access.
     * @return mixed The value of the property if it exists and is public.
     * @throws AxmException If the property does not exist or is not public.
     */
    public function __get($property)
    {
        $publicProperties = $this->getPublicProperties($this);
        if (isset($publicProperties[$property])) {
            return $property;
        }

        throw new AxmException(Axm::t('Raxm', 'Property $%s not found on component %s', [$property, $this->component]));
    }

    /**
     * Magic method to check if a property is set.
     *
     * @param string $property The name of the property to check.
     * @return bool True if the property is set and is public, false otherwise.
     * @throws AxmException If the property does not exist or is not public.
     */
    public function __isset($property)
    {
        if (null !== $this->__get($property)) {
            throw new AxmException(Axm::t('Raxm', 'Property $%s not found on component %s', [$property, $this->component]));
        }

        return true;
    }
}
