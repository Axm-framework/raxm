export const PREFIX_STRING  = 'axm'
export const PREFIX_REGEX   = PREFIX_STRING + '\\:';
export const PREFIX_DISPLAY = PREFIX_STRING + ':';


let directives = {}

export function directive(name, callback) {
    directives[name] = callback
}

export function initDirectives(el, component) {
    let elDirectives = getDirectives(el)

    Object.entries(directives).forEach(([name, callback]) => {
        elDirectives.directives
            .filter(({ value }) => value === name)
            .forEach(directive => {
                callback({
                    el,
                    directive,
                    component
                })
            })
    })
}

export default function getDirectives(el) {
    return new DirectiveManager(el)
}

class DirectiveManager {
    constructor(el) {
        this.el = el
        this.directives = this.extractTypeModifiersAndValue()
    }

    all() {
        return this.directives
    }

    has(type) {
        return this.directives.map(directive => directive.type).includes(type)
    }

    missing(type) {
        return !this.has(type)
    }

    get(type) {
        return this.directives.find(directive => directive.type === type)
    }

    extractTypeModifiersAndValue() {

        return Array.from(this.el.getAttributeNames()
            // Filter only the raxm directives.
            .filter(name => name.match(new RegExp(PREFIX_REGEX)))
            // Parse out the type, modifiers, and value from it.
            .map(name => {
                const [type, ...modifiers] = name.replace(new RegExp(PREFIX_REGEX), '').split('.')

                return new Directive(type, modifiers, name, this.el)
            })
        )
    }

}

class Directive {
    constructor(type, modifiers, rawName, el) {
        this.type = type
        this.modifiers = modifiers
        this.rawName = rawName
        this.el = el
        this.eventContext
    }

    setEventContext(context) {
        this.eventContext = context
    }

    get value() {
        return this.el.getAttribute(this.rawName)
    }

    get method() {
        const { method } = this.parseOutMethodAndParams(this.value)

        return method
    }

    get params() {
        const { params } = this.parseOutMethodAndParams(this.value)

        return params
    }

    durationOr(defaultDuration) {
        let durationInMilliSeconds
        const durationInMilliSecondsString = this.modifiers.find(mod => mod.match(/([0-9]+)ms/))
        const durationInSecondsString      = this.modifiers.find(mod => mod.match(/([0-9]+)s/))

        if (durationInMilliSecondsString) {
            durationInMilliSeconds = Number(durationInMilliSecondsString.replace('ms', ''))
        } else if (durationInSecondsString) {
            durationInMilliSeconds = Number(durationInSecondsString.replace('s', '')) * 1000
        }

        return durationInMilliSeconds || defaultDuration
    }

    parseOutMethodAndParams(rawMethod) {
        let method = rawMethod
        let params = []
        const methodAndParamString = method.match(/(.*?)\((.*)\)/s)

        if (methodAndParamString) {
            method = methodAndParamString[1]

            // Use a function that returns it's arguments to parse and eval all params
            // This "$event" is for use inside the raxm event handler.
            let func = new Function('$event', `return (function () {
                for (var l=arguments.length, p=new Array(l), k=0; k<l; k++) {
                    p[k] = arguments[k];
                }
                return [].concat(p);
            })(${methodAndParamString[2]})`)

            params = func(this.eventContext)
        }

        return { method, params }
    }

    cardinalDirectionOr(fallback = 'right') {
        if (this.modifiers.includes('up'))    return 'up'
        if (this.modifiers.includes('down'))  return 'down'
        if (this.modifiers.includes('left'))  return 'left'
        if (this.modifiers.includes('right')) return 'right'
        
        return fallback
    }
}