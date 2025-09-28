// popup.js - Form handling for the Book a Demo form

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Magnific Popup for form modals
    if (typeof $.fn.magnificPopup !== 'undefined') {
        $('.set-popup').magnificPopup({
            type: 'inline',
            midClick: true,
            removalDelay: 300,
            mainClass: 'mfp-fade'
        });
    }

    // Handle Book a Demo form submission
    const demoForm = document.getElementById('demo-form');
    if (demoForm) {
        demoForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Basic form validation
            if (validateDemoForm()) {
                // Show loading state
                const submitBtn = demoForm.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> SENDING...';
                submitBtn.disabled = true;
                
                // Collect form data
                const formData = new FormData(demoForm);
                
                // Send data to PHP endpoint
                submitFormData(formData)
                    .then(response => {
                        // Show success message
                        showFormMessage(response.message, 'success');
                        demoForm.reset();
                        
                        // Close the popup after successful submission
                        setTimeout(() => {
                            if (typeof $.fn.magnificPopup !== 'undefined') {
                                $.magnificPopup.close();
                            }
                        }, 3000);
                    })
                    .catch(error => {
                        // Show error message
                        if (error.errors) {
                            // Handle validation errors from server
                            let errorMessage = 'Please correct the following errors:<br>';
                            error.errors.forEach(err => {
                                errorMessage += `• ${err}<br>`;
                            });
                            showFormMessage(errorMessage, 'error');
                        } else {
                            showFormMessage(error.message || 'There was an error submitting your request. Please try again or contact us directly.', 'error');
                        }
                    })
                    .finally(() => {
                        // Reset button state
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
            }
        });
    }

    // Form validation function
    function validateDemoForm() {
        const form = document.getElementById('demo-form');
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                highlightError(field);
            } else {
                removeErrorHighlight(field);
            }
            
            // Email validation
            if (field.name === 'work-email' && field.value.trim()) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(field.value)) {
                    isValid = false;
                    highlightError(field, 'Please enter a valid email address.');
                }
            }
        });
        
        return isValid;
    }

    // Highlight field with error
    function highlightError(field, customMessage = null) {
        field.style.borderColor = '#e74c3c';
        
        // Remove existing error message
        const existingError = field.parentNode.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }
        
        // Add error message
        const errorMessage = document.createElement('div');
        errorMessage.className = 'error-message';
        errorMessage.style.color = '#e74c3c';
        errorMessage.style.fontSize = '12px';
        errorMessage.style.marginTop = '5px';
        errorMessage.textContent = customMessage || field.getAttribute('data-msg') || 'This field is required.';
        
        field.parentNode.appendChild(errorMessage);
    }

    // Remove error highlighting
    function removeErrorHighlight(field) {
        field.style.borderColor = '';
        
        const errorMessage = field.parentNode.querySelector('.error-message');
        if (errorMessage) {
            errorMessage.remove();
        }
    }

    // Show form submission message
    function showFormMessage(message, type) {
        // Remove existing messages
        const existingMessages = document.querySelectorAll('.form-message');
        existingMessages.forEach(msg => msg.remove());
        
        // Create new message element
        const messageEl = document.createElement('div');
        messageEl.className = `form-message ${type}`;
        messageEl.style.padding = '10px';
        messageEl.style.margin = '10px 0';
        messageEl.style.borderRadius = '4px';
        messageEl.style.textAlign = 'center';
        messageEl.style.fontWeight = 'bold';
        
        if (type === 'success') {
            messageEl.style.backgroundColor = '#d4edda';
            messageEl.style.color = '#155724';
            messageEl.style.border = '1px solid #c3e6cb';
        } else {
            messageEl.style.backgroundColor = '#f8d7da';
            messageEl.style.color = '#721c24';
            messageEl.style.border = '1px solid #f5c6cb';
        }
        
        messageEl.innerHTML = message;
        
        // Insert message above the form
        const form = document.getElementById('demo-form');
        form.parentNode.insertBefore(messageEl, form);
        
        // Auto-remove message after 5 seconds (only for success messages)
        if (type === 'success') {
            setTimeout(() => {
                if (messageEl.parentNode) {
                    messageEl.parentNode.removeChild(messageEl);
                }
            }, 5000);
        }
    }

    // Submit form data to PHP endpoint
    function submitFormData(formData) {
        return new Promise((resolve, reject) => {
            // Convert FormData to URL-encoded format for PHP
            const data = new URLSearchParams();
            for (const pair of formData) {
                data.append(pair[0], pair[1]);
            }
            
            // Send POST request to PHP endpoint
            fetch('email-service.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: data
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    resolve(data);
                } else {
                    reject(data);
                }
            })
            .catch(error => {
                console.error('Error submitting form:', error);
                reject({
                    message: 'Network error occurred. Please try again later.'
                });
            });
        });
    }

    // Add real-time validation
    const demoFormFields = document.querySelectorAll('#demo-form input, #demo-form select, #demo-form textarea');
    demoFormFields.forEach(field => {
        field.addEventListener('blur', function() {
            if (this.hasAttribute('required') && !this.value.trim()) {
                highlightError(this);
            } else {
                removeErrorHighlight(this);
            }
        });
        
        field.addEventListener('input', function() {
            if (this.value.trim()) {
                removeErrorHighlight(this);
            }
        });
    });
});
