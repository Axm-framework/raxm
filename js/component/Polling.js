import MethodAction from '../action/method.js'
import getDirectives from '../util/raxm-directives.js'
import store from '../Store.js'

export default function () {
    store.registerHook('element.initialized', (el, component) => {
        let directives = getDirectives(el)

        if (directives.missing('poll')) return

        let intervalId = fireActionOnInterval(el, component)

        component.addListenerForTeardown(() => {
            clearInterval(intervalId)
        })

        el.__axm_polling_interval = intervalId
    })

    store.registerHook('element.updating', (from, to, component) => {
        if (from.__axm_polling_interval !== undefined) return

        if (getDirectives(from).missing('poll') && getDirectives(to).has('poll')) {
            setTimeout(() => {
                let intervalId = fireActionOnInterval(from, component)

                component.addListenerForTeardown(() => {
                    clearInterval(intervalId)
                })

                from.__axm_polling_interval = intervalId
            }, 0)
        }
    })
}

function fireActionOnInterval(node, component) {
    let interval = getDirectives(node).get('poll').durationOr(2000);

    return setInterval(() => {
        if (node.isConnected === false) return

        let directives = getDirectives(node)

        // Don't poll when directive is removed from element.
        if (directives.missing('poll')) return

        const directive = directives.get('poll')
        const method = directive.method || '$refresh'

        // Don't poll when the tab is in the background.
        // (unless the "axm:poll.keep-alive" modifier is attached)
        if (store.raxmIsInBackground && !directive.modifiers.includes('keep-alive')) {
            // This "Math.random" business effectivlly prevents 95% of requests
            // from executing. We still want "some" requests to get through.
            if (Math.random() < .95) return
        }

        // Only poll visible elements. Visible elements are elements that
        // are visible in the current viewport.
        if (directive.modifiers.includes('visible') && !inViewport(directive.el)) {
            return
        }

        // Don't poll if liveaxm is offline as well.
        if (store.raxmIsOffline) return

        component.addAction(new MethodAction(method, directive.params, node))
    }, interval);
}

function inViewport(el) {
    var bounding = el.getBoundingClientRect();

    return (
        bounding.top < (window.innerHeight || document.documentElement.clientHeight) &&
        bounding.left < (window.innerWidth || document.documentElement.clientWidth) &&
        bounding.bottom > 0 &&
        bounding.right > 0
    );
}
