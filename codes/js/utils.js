function getCsrfToken() {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    
    if (!metaTag) {
        console.warn("Security Warning: CSRF meta tag is missing from the HTML <head>!");
        return '';
    }
    
    return metaTag.getAttribute('content');
}