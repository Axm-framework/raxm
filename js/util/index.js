
import debounce from './debounce.js'
import getDirectives from './directives.js'
import walk from './walk.js'
import dispatch from './dispatch.js'
import getCsrfToken from './getCsrfToken.js'

export function kebabCase(subject) {
    return subject.replace(/([a-z])([A-Z])/g, '$1-$2').replace(/[_\s]/, '-').toLowerCase()
}

export function tap(output, callback) {
    callback(output)

    return output
}
