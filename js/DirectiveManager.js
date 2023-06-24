import MessageBus from "./MessageBus.js"

export default {
    directives: new MessageBus,

    register(name, callback) {
        if (this.has(name)) {
            throw `Axm: Directive already registered: [${name}]`
        }

        this.directives.register(name, callback)
    },

    call(name, el, directive, component) {
        this.directives.call(name, el, directive, component)
    },

    has(name) {
        return this.directives.has(name)
    },
}
