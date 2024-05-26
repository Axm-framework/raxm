<?php

declare(strict_types=1);

namespace Axm\Raxm;

use Exception;
use Views\View;
use Http\Request;
use Http\Response;
use RuntimeException;
use Axm\Raxm\ReceivesEvents;
use Axm\Raxm\LifecycleManager;
use Axm\Raxm\ComponentCheckSum;
use Axm\Raxm\ComponentProperties;
use App\Controllers\BaseController;
use Axm\Raxm\Support\HandlesActions;
use Axm\Raxm\Support\ValidatesInput;
use Axm\Raxm\HtmlRootTagAttributeAdder;
use Axm\Raxm\Support\InteractsWithProperties;

/**
 * Abstract base class for Raxm components.
 *
 * This class serves as the base class for all Raxm components,
 * providing common functionality and methods.
 * Raxm components are used to build dynamic web interfaces with 
 * real-time updates and interactions.
 */
abstract class Component extends BaseController
{
    use ReceivesEvents;
    use InteractsWithProperties;
    use HandlesActions;
    use ValidatesInput;

    protected ?Request $request;
    protected ?Response $response;
    protected bool $shouldSkipRendering = false;
    protected ?array $serverMemo = null;
    protected ?array $fingerprint = null;
    protected ?array $updates = null;
    protected ?array $publicProperties = [];
    protected ?string $preRenderedView;
    protected bool $ifActionIsRedirect = false;
    protected bool $ifActionIsNavigate = false;
    public ?string $id;
    public ?array $effects = [];
    protected ?int $id_p;
    protected ?string $component;
    protected ?string $type;
    protected ?string $method;
    protected ?array $params;
    protected ?array $payload;
    protected array $return = [];
    protected array $eventQueue = [];
    protected array $dispatchQueue = [];
    protected array $listeners = [];
    protected array $queryString = [];
    protected array $rules = [];
    protected $messages;
    public $tmpfile;

    /**
     * Constructor for the Raxm Component.
     */
    public function __construct()
    {
        $app = app();
        $this->request = $app->request ?? null;
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
    private function run()
    {
        $this->isRaxmRequest();
        $this->extractDataRequest($this->request);
        $this->hydrateFromServerMemo();
        $this->hydratePayload();
        $this->dispatchEvents();
        $this->compileResponse();
        $this->sendJsonResponse();
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
        if ($this->request->getHeader('X-AXM') != true) {
            throw new Exception('This request is not a Raxm request');
        }
    }

    /**
     * Extract data from the request.
     *
     * This method extracts data from the HTTP request, including server memo,
     * updates, and fingerprint data. It sets the component's ID and name based on the fingerprint.
     */
    private function extractDataRequest(Request $request): void
    {
        $this->serverMemo = $request->serverMemo ?? [];
        $this->updates = $request->updates ?? [];
        $this->fingerprint = $request->fingerprint ?? [];

        [$this->id, $this->component] = [
            $this->fingerprint['id'],
            $this->fingerprint['name']
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
        foreach ($this->updates ?? [] as $payload) {
            $this->payload = $payload['payload'];

            $this->type = $payload['type'];
            $this->id_p = (int) $payload['payload']['id'];
            $this->method = $payload['payload']['method'] ?? null;
            $this->params = $payload['payload']['params'] ?? null;
        }
    }

    /**
     * Dispatch events based on the payload type.
     * This method dispatches events based on the type of payload 
     * received from the client.
     */
    private function dispatchEvents()
    {
        match ($this->type) {
            'syncInput' => $this->syncInputData(),
            'callMethod' => $this->callMethod($this->method, $this->params),
            'fireEvent' => $this->fireEvent($this->method, $this->params, $this->id_p),

            default => throw new Exception('Unknown event type: ' . $this->type)
        };
    }

    /**
     * Sync input data based on updates.
     * This method syncs input data based on updates received from the client.
     */
    private function syncInputData(): void
    {
        foreach ($this->updates as $update) {
            $name = $update['payload']['name'] ?? null;
            $value = $update['payload']['value'] ?? null;

            $this->syncInput($name, $value);
        }
    }

    /**
     * Initialize the Raxm component.
     *
     * This method initializes the Raxm component, setting its ID and preparing 
     * the response for the client.
     */
    private function initialInstance(?string $id = null): string
    {
        $this->id = $id ?? bin2hex(random_bytes(10));   // Generate a random ID if one is not already set.
        $this->prepareResponse();                      // Prepare the response that will be sent to the client.

        return $this->html();                        // Return the response to the client.
    }

    /**
     * Get the HTML representation of the component.
     *
     * This method returns the HTML representation of the component, 
     * which is stored in the 'effects' array.
     */
    private function html(): ?string
    {
        return $this->effects['html'] ?? null;
    }

    /**
     * Embed the component's data in the HTML representation.
     *
     * This method embeds the component's data in the HTML representation 
     * by adding attributes to the HTML root tag.
     */
    private function embedThyselfInHtml(): void
    {
        if (!$html = $this->renderToView())
            return;

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
    private function embedIdInHtml(): void
    {
        if (!$html = $this->effects['html'] ?? null)
            return;

        $this->effects['html'] = (new HtmlRootTagAttributeAdder)($html, [
            'id' => $this->id,
        ]);
    }

    /**
     * It is used to wrap the HTML content of the 'effects' array inside a div element.
     */
    private function wrapInDiv(): void
    {
        if (!$html = $this->effects['html'] ?? null) {
            return;
        }

        // Adding line breaks and indentation to HTML before wrapping it in a div
        $indentedHtml = preg_replace('/^/m', '    ', $html);
        $this->effects['html'] = sprintf("<div>\n%s\n</div>\n", $indentedHtml);
    }

    /**
     * This method returns an array of component data without the HTML representation.
     */
    private function toArrayWithoutHtml(): array
    {
        $fingerprint = $this->fingerprint ?? LifecycleManager::initialFingerprint();
        $effects = array_diff_key($this->effects, ['html' => null]) ?: LifecycleManager::initialEffects();
        $serverMemo = $this->serveMemo() ?? LifecycleManager::createDataServerMemo();

        return compact('fingerprint', 'effects', 'serverMemo');
    }

    /**
     * Render the component to a view.
     *
     * This method renders the component to a view, which is either returned by 
     * the 'render' method or a default view.
     */
    private function renderToView(): ?string
    {
        return $this->preRenderedView = $this->getView();
    }

    /**
     * Call the component's 'render' method.
     *
     * This method calls the 'render' method of the component or falls back to a default 
     * view if the 'render' method is not defined.
     */
    private function callRender(): ?string
    {
        $this->publicProperties = View::$tempData = $this->getPublicProperties();

        return $this->render();
    }

    /**
     * Get the view for the component.
     *
     * This method retrieves the view for the component, either by calling 
     * the 'render' method or using a default view name.
     */
    private function getView(): ?string
    {
        $view = method_exists($this, 'render')
            ? $this->callRender() : view('raxm.' . $this->getComponentName());

        return $view;
    }

    /**
     * Get the name of the component.
     */
    private function getComponentName(): string
    {
        return ComponentManager::componentName();
    }

    /**
     * Compile and mount the component.
     */
    public function index(object $component): string
    {
        return ComponentManager::mountComponent($component);
    }

    /**
     * Sets component properties based on provided parameters.
     * Populates public properties with valid values from the given array.
     */
    protected function mount(?array $params = []): self
    {
        $this->publicProperties = ComponentProperties::getPublicProperties($this);
        foreach ($params as $property => $value) {
            if (
                isset($this->publicProperties[$property]) && (is_array($value)
                    || is_scalar($value) || is_null($value))
            ) {
                // Assign the value to the property.
                $this->{$property} = $value;
            }
        }

        return $this;
    }

    /**
     * Get the public properties of the component.
     */
    private function getPublicProperties(): array
    {
        return ComponentProperties::getPublicProperties($this);
    }

    /**
     * Prepare the response data for the client.
     *
     * This method prepares the response data that will be sent to the client, 
     * including effects, server memo, and checksum.
     */
    private function prepareResponse(): array
    {
        return [
            'effects' => $this->effects(),
            'serverMemo' => $this->serveMemo(),
        ];
    }

    /**
     * Generates and serves a memo containing specific information.
     */
    private function serveMemo(): array
    {
        $serverMemo = [
            'htmlHash' => randomId(8),                    // Generate a random HTML hash with 8 characters.
            'data' => $this->dataResponse(),             // Get the data response using the dataResponse() method.
            'checksum' => $this->checkSumAndGenerate(
                $this->serverMemo['checksum'] ?? '',   // Get the current checksum of the memo or a default value.
                $this->fingerprint ?? [],             // Get the fingerprint or an empty array if not defined.
                $this->serverMemo ?? []              // Get the current memo or an empty array if not defined.
            )
        ];

        return $serverMemo;
    }

    /**
     * Get the effects data for the client.
     *
     * This method retrieves the effects data, including HTML, dirty data, events,
     * and listeners, to be sent to the client.
     */
    private function effects(): array
    {
        $this->embedThyselfInHtml();
        $this->embedIdInHtml();
        $this->wrapInDiv();

        $effects = [
            'html' => $this->html(),
            'dirty' => $this->getChangedData(),
            'emits' => $this->getEventQueue(),
            'listeners' => $this->getEventsBeingListenedFor(),
            'dispatches' => $this->getDispatchQueue(),
        ];

        // Check if $this->ifActionIsRedirect is true before adding 'redirect' to the array.
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
     * This method adds additional effects data to the component's effects array.
     */
    private function addEffects(string $key, $value)
    {
        return $this->effects[$key] = $value;
    }

    /**
     * Get the URL to redirect to.
     */
    private function getRedirectTo(): ?string
    {
        if (method_exists($this, 'redirect')) {
            return generateUrl($this->redirect());
        }
    }

    /**
     * Check and generate the checksum for data integrity.
     */
    private function checkSumAndGenerate(string $checksum, array $fingerprint, ?array $memo): string
    {
        if (ComponentCheckSum::check($checksum, $fingerprint, $memo))
            throw new RuntimeException("Raxm encountered corrupt data when 
                trying to hydrate the $this->component component. \n" .
                "Ensure that the [name, id, data] 
                of the Raxm component wasn't tampered with between requests.");

        return ComponentCheckSum::generate($fingerprint, $memo);
    }

    /**
     * Get the changed data properties.
     *
     * This method retrieves the properties of the component's data that have 
     * changed compared to the server memo.
     */
    private function getChangedData(): array
    {
        $changedData = [];
        foreach ($this->serverMemo['data'] ?? [] as $key => $value) {
            if (isset($this->{$key}) && $this->{$key} != $value) {
                $changedData[] = $key;
            }
        }

        return $changedData;
    }

    /**
     * Get the data to include in the server response.
     *
     * This method retrieves the data to include in the server response, 
     * excluding any properties that are not part of the component's public properties.
     */
    private function dataResponse(): array
    {
        return array_filter($this->publicProperties, function ($key) {
            return property_exists($this, $key);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Prepare and send a JSON response to the client.
     */
    private function compileResponse(): array
    {
        return $this->return = $this->prepareResponse();
    }

    /**
     * Sends a JSON response using the response object and the specified data.
     */
    private function sendJsonResponse(): void
    {
        $this->response->toJson($this->return);
    }

    /**
     * Handle dynamic method calls.
     */
    public function __call(string $method, array $params)
    {
        if (
            in_array($method, ['mount', 'hydrate', 'dehydrate', 'updating', 'updated'])
            || str($method)->startsWith(['updating', 'updated', 'hydrate', 'dehydrate'])
        ) {
            return;
        }

        $className = static::class;
        if (!method_exists($this, $method)) {
            throw new Exception(sprintf('Method [ %s ] does not exist', "$className::$method()"));
        }

        return $this->$method(...$params);
    }

    /**
     * Magic method to access properties of the class.
     */
    public function __get(string $property)
    {
        $publicProperties = $this->getPublicProperties();
        if (isset($publicProperties[$property])) {
            return $property;
        }

        throw new Exception(sprintf('Property [ $%s ] not found on component [ %s ] ', $property, $this->component));
    }

    /**
     * Magic method to check if a property is set.
     */
    public function __isset(string $property): bool
    {
        if (null !== $this->__get($property)) {
            throw new Exception(sprintf('Property [ $%s ] not found on component [ %s ] ', $property, $this->component));
        }

        return true;
    }
}
