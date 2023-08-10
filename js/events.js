import { isFunction } from '../utils';

    /**
     * Dispatch a custom browser event...
     */
    export function dispatch(event, payload) {
        document.dispatchEvent(new CustomEvent(`synthetic:${event}`, { detail: payload }));
    }


    /**
     * Our internal event listener bus...
     */
    const listeners = new Map();


    /**
     * Register a callback to run when an event is triggered...
     */
    export function on(name, callback) {
       
        if (!listeners.has(name)) {
            listeners.set(name, []);
        }

        listeners.get(name).push(callback);

        // Return an "off" callback to remove the listener...
        return () => {
            listeners.set(name, listeners.get(name).filter(i => i !== callback));
        };
    }


    /**
     * In addition to triggering an event, this method allows you to
     * defer running callbacks returned from listeners and pass a
     * value through each one so they can act like middleware.
     */
    export function trigger(name, ...params) {
      
        const callbacks = listeners.get(name) || [];
        const finishers = callbacks.filter(isFunction);

        let latestResult = params;

        finishers.forEach(finisher => {
            latestResult = finisher(...latestResult);
        });

        return latestResult;
    }
