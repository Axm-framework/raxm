import { directive } from "../directives.js";
import MethodAction from "../action/method.js";
import { addAction } from "../commit.js";

directive("init", ({ el, directive, component }) => {
    const method = directive.expression ?? "$refresh";

    addAction(component, new MethodAction(method, directive.params, el));
});
