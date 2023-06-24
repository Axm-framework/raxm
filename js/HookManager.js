import MessageBus from './MessageBus.js'

export default {
    availableHooks: [
        /**
         * Public Hooks
         */
        'component.initialized',
        'element.initialized',
        'element.updating',
        'element.updated',
        'element.removed',
        'message.sent',
        'message.failed',
        'message.received',
        'message.processed',

        /**
         * Private Hooks
         */
        'interceptAxmModelSetValue',
        'interceptAxmModelAttachListener',
        'beforeReplaceState',
        'beforePushState',
    ],

    bus: new MessageBus(),

    register(name, callback) {
        if (! this.availableHooks.includes(name)) {
            throw `Axm: Referencing unknown hook: [${name}]`
        }

        this.bus.register(name, callback)
    },

    call(name, ...params) {
        this.bus.call(name, ...params)
    },
}
