import { getDirectives, PREFIX_REGEX } from '../directives.js';
import { dataGet } from "../util/utils.js";
import store from '../store.js'

/**
 * This is intended to isolate all native DOM operations. The operations that happen
 * one specific element will be instance methods, the operations you would normally
 * perform on the "document" (like "document.querySelector") will be static methods.
 */
export default {
    rootComponentElements() {
        return Array.from(document.querySelectorAll(`[${PREFIX_REGEX}id]`))
    },

    rootComponentElementsWithNoParents(node = null) {
        if (node === null) {
            node = document
        }

        // In CSS, it's simple to select all elements that DO have a certain ancestor.
        // However, it's not simple (kinda impossible) to select elements that DONT have
        // a certain ancestor. Therefore, we will flip the logic: select all roots that DO have
        // have a root ancestor, then select all roots that DONT, then diff the two.

        // Convert NodeLists to Arrays so we can use ".includes()". Ew.
        const allEls = Array.from(node.querySelectorAll(`[${PREFIX_REGEX}initial-data]`))
        const onlyChildEls = Array.from(node.querySelectorAll(`[${PREFIX_REGEX}initial-data] [${PREFIX_REGEX}initial-data]`))

        return allEls.filter(el => !onlyChildEls.includes(el))
    },

    allModelElementsInside(root) {
        return Array.from(root.querySelectorAll(`[${PREFIX_REGEX}model]`))
    },

    getByAttributeAndValue(attribute, value) {
        return document.querySelector(`[${PREFIX_REGEX}${attribute}="${value}"]`)
    },

    nextFrame(fn) {
        requestAnimationFrame(() => {
            requestAnimationFrame(fn.bind(this))
        })
    },

    closestRoot(el) {
        return this.closestByAttribute(el, 'id')
    },

    closestByAttribute(el, attribute) {
        const closestEl = el.closest(`[${PREFIX_REGEX}${attribute}]`)

        if (!closestEl) {
            throw `
                Raxm Error:\n
                Cannot find parent element in DOM tree containing attribute: [${PREFIX_REGEX}${attribute}].\n
                Usually this is caused by Raxm's DOM-differ not being able to properly track changes.\n
                Reference the following guide for common causes: https://axm-raxm.com/docs/troubleshooting \n
                Referenced element:\n
            ${el.outerHTML}`
        }

        return closestEl
    },

    isComponentRootEl(el) {
        return this.hasAttribute(el, 'id')
    },

    hasAttribute(el, attribute) {
        return el.hasAttribute(`${PREFIX_REGEX}${attribute}`)
    },

    getAttribute(el, attribute) {
        return el.getAttribute(`${PREFIX_REGEX}${attribute}`)
    },

    removeAttribute(el, attribute) {
        return el.removeAttribute(`${PREFIX_REGEX}${attribute}`)
    },

    setAttribute(el, attribute, value) {
        return el.setAttribute(`${PREFIX_REGEX}${attribute}`, value)
    },

    hasFocus(el) {
        return el === document.activeElement
    },

    isInput(el) {
        return ['INPUT', 'TEXTAREA', 'SELECT'].includes(
            el.tagName
        )
    },

    isTextInput(el) {
        return (
            ['INPUT', 'TEXTAREA'].includes(el.tagName) &&
           !['checkbox', 'radio'].includes(el.type)
        )
    },

    valueFromInput(el, component) {

        if (el.type === 'checkbox') {
            let modelName = getDirectives(el).get('model').value
            // If there is an update from axm:model.defer in the chamber,
            // we need to pretend that is the actual data from the server.
            let modelValue = component.deferredActions[modelName]
                ? component.deferredActions[modelName].payload.value
                : dataGet(component.data, modelName)

            if (Array.isArray(modelValue)) {
                return this.mergeCheckboxValueIntoArray(el, modelValue)
            }

            if (el.checked) {
                return el.getAttribute('value') || true
            } else {
                return false
            }
        } else if (el.tagName === 'SELECT' && el.multiple) {
            return this.getSelectValues(el)
        }

        return el.value
    },

    mergeCheckboxValueIntoArray(el, arrayValue) {
        if (el.checked) {
            return arrayValue.includes(el.value)
                ? arrayValue
                : arrayValue.concat(el.value)
        }

        return arrayValue.filter(item => item != el.value)
    },

    setInputValueFromModel(el, component) {
        const modelString = getDirectives(el).get('model').value
        const modelValue  = dataGet(component.data, modelString)

        // Don't manually set file input's values.
        if (
            el.tagName === 'INPUT' &&
            el.type === 'file'
        )
            return

        this.setInputValue(el, modelValue)
    },

    setInputValue(el, value) {
        store.callHook('interceptRaxmModelSetValue', value, el)

        if (el.type === 'radio') {
            el.checked = el.value == value
        } else if (el.type === 'checkbox') {
            if (Array.isArray(value)) {
                // I'm purposely not using Array.includes here because it's
                // strict, and because of Numeric/String mis-casting, I
                // want the "includes" to be "fuzzy".
                let valueFound = false
                value.forEach(val => {
                    if (val == el.value) {
                        valueFound = true
                    }
                })

                el.checked = valueFound
            } else {
                el.checked = !!value
            }
        } else if (el.tagName === 'SELECT') {
            this.updateSelect(el, value)
        } else {
            value = value === undefined ? '' : value

            el.value = value
        }
    },

    getSelectValues(el) {
        return Array.from(el.options)
            .filter(option => option.selected)
            .map(option => {
                return option.value || option.text
            })
    },

    updateSelect(el, value) {
        const arrayWrappedValue = [].concat(value).map(value => {
            return value + ''
        })

        Array.from(el.options).forEach(option => {
            option.selected = arrayWrappedValue.includes(option.value)
        })
    },

    isAsset(el) {
        return (
            el.tagName.toLowerCase() === 'link' && el.getAttribute('rel').toLowerCase() === 'stylesheet' ||
            el.tagName.toLowerCase() === 'style' ||
            el.tagName.toLowerCase() === 'script'
        )
    },

    isScript(el) {
        return el.tagName.toLowerCase() === 'script'
    },
    
    cloneScriptTag(el) {
        let script = document.createElement('script')
        script.textContent = el.textContent
        script.async = el.async
        for (let attr of el.attributes) {
            script.setAttribute(attr.name, attr.value)
        }
        return script
    },

    ignoreAttributes(subject, attributesToRemove) {
        let result = subject
        attributesToRemove.forEach(attr => {
            const regex = new RegExp(`${attr}="[^"]*"|${attr}='[^']*'`, 'g')
            result = result.replace(regex, '')
        })
        return result.trim()
    }
    
    
}
