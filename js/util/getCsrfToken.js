export default function getCsrfToken() {
    const tokenTag = document.head.querySelector('meta[name="csrf-token"]')

    if (tokenTag) {
        return tokenTag.content
    }

    return window.axm_token ?? undefined
}
