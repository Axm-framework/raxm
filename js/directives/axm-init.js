import { directive } from "../directives.js"
import MethodAction from '../action/method.js'

directive('init', ({ el, directive, component }) => {

    const method = directive.value ? directive.method : '$refresh'

    component.addAction(new MethodAction(method, directive.params, el))
})
