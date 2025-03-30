// Loading animation fix
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - hiding loading animation');
    document.body.classList.add('loaded');
    const loading = document.querySelector('.loading');
    if (loading) {
        loading.style.opacity = '0';
        setTimeout(function() {
            loading.style.display = 'none';
            console.log('Loading animation hidden');
        }, 300);
    } else {
        console.log('Loading element not found');
    }
});

// Fallback to hide loading animation if the page takes too long
setTimeout(function() {
    console.log('Fallback timeout triggered');
    document.body.classList.add('loaded');
    const loading = document.querySelector('.loading');
    if (loading) {
        loading.style.opacity = '0';
        setTimeout(function() {
            loading.style.display = 'none';
            console.log('Loading animation hidden by fallback');
        }, 300);
    }
}, 2000);

// Additional fallback - force hide any loading elements
window.addEventListener('load', function() {
    console.log('Window loaded - forcing hide of loading animation');
    document.body.classList.add('loaded');
    const loading = document.querySelector('.loading');
    if (loading) {
        loading.style.opacity = '0';
        loading.style.display = 'none';
        console.log('Loading animation forcibly hidden');
    }
}); 