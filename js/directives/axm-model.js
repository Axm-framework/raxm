import { directive, PREFIX_REGEX, PREFIX_DISPLAY } from "../directives.js";
import DeferredModelAction from "../action/model.js";
import ModelAction from "../action/model.js";
import store from "../store.js";
import { addAction, modelSyncDebounce } from "../commit.js";
import DOM from "../dom/dom.js";
import { handleFileUpload } from "../features/supportFileUploads.js";

/**
 * Directive for model binding between DOM element and component.
 */
directive("model", ({ el, directive, component, cleanup }) => {
    let { expression, modifiers } = directive;

    if (!expression) {
        return console.warn(`Raxm: [${PREFIX_DISPLAY}model] is missing a value.`, el);
    }

    if (componentIsMissingProperty(component, expression)) {
        return console.warn(
            `Raxm: [${PREFIX_DISPLAY}model="${expression}"] property does not exist on component: [${component.name}]`,
            el
        );
    }

    // Handle file uploads differently...
    if (el.type && el.type.toLowerCase() === "file") {
        return handleFileUpload(el, expression, component, cleanup);
    }

    DOM.setInputValueFromModel(el, component);

    attachModelListener(el, directive, component);
});

/**
 * Attach event listener for model binding.
 */
function attachModelListener(el, directive, component) {
    let { expression, modifiers } = directive;

    el.isRaxmModel = true;

    let isLive = modifiers.includes("live");
    let isLazy = modifiers.includes("lazy");
    let isDefer = modifiers.includes("defer");
    let isDebounced = modifiers.includes("debounce");

    store.callHook("interceptRaxmModelAttachListener", directive, el, component, expression);

    const event = el.tagName === "SELECT" || ["checkbox", "radio"].includes(el.type) || isLazy
        ? "change"
        : "input";

    const debounceIf = (condition, callback, time) => {
        return condition ? modelSyncDebounce(callback, time) : callback;
    };

    let handler = debounceIf(DOM.isTextInput(el) && !isDebounced && !isLazy, (e) => {
        let model = directive.value;
        let el = e.target;

        let value = e instanceof CustomEvent && e.detail !== undefined
            ? e.detail ?? e.target.value
            : DOM.valueFromInput(el, component);

        if (isDefer) {
            addAction(component, new DeferredModelAction(model, value, el));
        } else {
            addAction(component, new ModelAction(model, value, el));
        }
    }, directive.durationOr(150));

    el.addEventListener(event, handler);

    component.addListenerForTeardown(() => {
        el.removeEventListener(event, handler);
    });

    let isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);

    if (isSafari) {
        el.addEventListener("animationstart", (e) => {
            if (e.animationName !== "raxmautofill") return;

            e.target.dispatchEvent(new Event("change", { bubbles: true }));
            e.target.dispatchEvent(new Event("input", { bubbles: true }));
        });
    }
}

/**
 * Check if the component is missing a property.
 */
function componentIsMissingProperty(component, property) {
    if (property.startsWith("$parent")) {
        let parent = closestComponent(component.el.parentElement, false);

        if (!parent) return true;

        return componentIsMissingProperty(parent, property.split("$parent.")[1]);
    }

    let baseProperty = property.split(".")[0];

    return !Object.keys(component.serverMemo.data).includes(baseProperty);
}
