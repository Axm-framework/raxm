import store from '../Store.js'
import axmDirectives from '../util/axm-directives.js'

var offlineEls = [];

export default function () {
    store.registerHook('element.initialized', el => {
        if (axmDirectives(el).missing('offline')) return

        offlineEls.push(el)
    })

    window.addEventListener('offline', () => {
        store.axmIsOffline = true

        offlineEls.forEach(el => {
            toggleOffline(el, true)
        })
    })

    window.addEventListener('online', () => {
        store.axmIsOffline = false

        offlineEls.forEach(el => {
            toggleOffline(el, false)
        })
    })

    store.registerHook('element.removed', el => {
        offlineEls = offlineEls.filter(el => !el.isSameNode(el))
    })
}

function toggleOffline(el, isOffline) {
    let directives = axmDirectives(el)
    let directive = directives.get('offline')

    if (directive.modifiers.includes('class')) {
        const classes = directive.value.split(' ')
        if (directive.modifiers.includes('remove') !== isOffline) {
            el.classList.add(...classes)
        } else {
            el.classList.remove(...classes)
        }
    } else if (directive.modifiers.includes('attr')) {
        if (directive.modifiers.includes('remove') !== isOffline) {
            el.setAttribute(directive.value, true)
        } else {
            el.removeAttribute(directive.value)
        }
    } else if (!directives.get('model')) {
        el.style.display = isOffline ? 'inline-block' : 'none'
    }
}
