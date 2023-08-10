import store from '../Store.js'
import DOM from '../dom/dom.js'
import raxmDirectives from '../util/raxm-directives.js'

export default function () {
    store.registerHook('component.initialized', component => {
        component.dirtyEls = []
    })

    store.registerHook('element.initialized', (el, component) => {
        if (raxmDirectives(el).missing('dirty')) return

        component.dirtyEls.push(el)
    })

    store.registerHook(
        'interceptRaxmModelAttachListener',
        (directive, el, component) => {
            let property = directive.value

            el.addEventListener('input', () => {
                component.dirtyEls.forEach(dirtyEl => {
                    let directives = raxmDirectives(dirtyEl)
                    if (
                        (directives.has('model') &&
                            directives.get('model').value ===
                            property) ||
                        (directives.has('target') &&
                            directives
                                .get('target')
                                .value.split(',')
                                .map(s => s.trim())
                                .includes(property))
                    ) {
                        let isDirty = DOM.valueFromInput(el, component) != component.get(property)

                        setDirtyState(dirtyEl, isDirty)
                    }
                })
            })
        }
    )

    store.registerHook('message.received', (message, component) => {
        component.dirtyEls.forEach(element => {
            if (element.__axm_dirty_cleanup) {
                element.__axm_dirty_cleanup()
                delete element.__axm_dirty_cleanup
            }
        })
    })

    store.registerHook('element.removed', (el, component) => {
        component.dirtyEls.forEach((element, index) => {
            if (element.isSameNode(el)) {
                component.dirtyEls.splice(index, 1)
            }
        })
    })
}

function setDirtyState(el, isDirty) {
    const directive = raxmDirectives(el).get('dirty')

    if (directive.modifiers.includes('class')) {
        const classes = directive.value.split(' ')
        if (directive.modifiers.includes('remove') !== isDirty) {
            el.classList.add(...classes)
            el.__axm_dirty_cleanup = () => el.classList.remove(...classes)
        } else {
            el.classList.remove(...classes)
            el.__axm_dirty_cleanup = () => el.classList.add(...classes)
        }
    } else if (directive.modifiers.includes('attr')) {
        if (directive.modifiers.includes('remove') !== isDirty) {
            el.setAttribute(directive.value, true)
            el.__axm_dirty_cleanup = () =>
                el.removeAttribute(directive.value)
        } else {
            el.removeAttribute(directive.value)
            el.__axm_dirty_cleanup = () =>
                el.setAttribute(directive.value, true)
        }
    } else if (!raxmDirectives(el).get('model')) {
        el.style.display = isDirty ? 'inline-block' : 'none'
        el.__axm_dirty_cleanup = () =>
            (el.style.display = isDirty ? 'none' : 'inline-block')
    }
}
