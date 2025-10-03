document.addEventListener('DOMContentLoaded', function () {
  const demoForm = document.getElementById('demo-form');

  if (demoForm) {
    demoForm.addEventListener('submit', function (e) {
      e.preventDefault();
      e.stopImmediatePropagation();

      // Get form data
      const formData = new FormData(this);

      // Show loading state
      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;
      submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> SENDING...';
      submitBtn.disabled = true;

      // Send to PHP
      fetch('email-service.php', {
        method: 'POST',
        body: formData,
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error('Server error: ' + response.status);
          }
          return response.json();
        })
        .then((data) => {
          if (data.status === 'success') {
            // SUCCESS - show success popup
            showPopupMessage('✅ Success! Your demo request has been submitted.', 'success');
            demoForm.reset();
          } else {
            // FAILURE - show error popup
            throw new Error(data.message || 'Submission failed');
          }
        })
        .catch((error) => {
          // ANY ERROR - show error popup
          showPopupMessage('❌ Error: ' + error.message, 'error');
        })
        .finally(() => {
          submitBtn.innerHTML = originalText;
          submitBtn.disabled = false;
        });
    });
  }

  // Replace your current showFormMessage with this version
  function showPopupMessage(message, type) {
    // Remove existing messages
    document.querySelectorAll('.form-message-overlay').forEach((el) => el.remove());

    // Overlay
    const overlay = document.createElement('div');
    overlay.className = 'form-message-overlay';

    // Message box
    const box = document.createElement('div');
    box.className = `form-message-box ${type}`;
    box.innerHTML = `
        <div class="icon">${type === 'success' ? '✅' : '❌'}</div>
        <div class="text">${message}</div>
        <button class="close-btn">Got it</button>
    `;

    overlay.appendChild(box);
    document.body.appendChild(overlay);

    // Styles (scoped inline so you don’t need external CSS)
    const styles = `
    .form-message-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.45);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        animation: fadeIn 0.3s ease forwards;
    }

    .form-message-box {
        background: #fff;
        border-radius: 18px;
        padding: 40px 50px;
        box-shadow: 0 15px 45px rgba(0,0,0,0.2);
        text-align: center;
        max-width: 500px;
        width: 90%;
        animation: scaleIn 0.35s cubic-bezier(0.18, 0.89, 0.32, 1.28);
    }

    .form-message-box.success { border-top: 6px solid #28a745; }
    .form-message-box.error { border-top: 6px solid #dc3545; }

    .form-message-box .icon {
        font-size: 60px;
        margin-bottom: 20px;
    }

    .form-message-box .text {
        font-size: 18px;
        font-weight: 600;
        color: #333;
        margin-bottom: 25px;
        line-height: 1.4;
    }

    .form-message-box .close-btn {
        background: ${type === 'success' ? '#28a745' : '#dc3545'};
        color: #fff;
        border: none;
        padding: 12px 28px;
        font-size: 16px;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.2s ease;
    }

    .form-message-box.success .close-btn:hover { background: #218838; }
    .form-message-box.error .close-btn:hover { background: #c82333; }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes scaleIn {
        from { transform: scale(0.8); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    `;
    const styleTag = document.createElement('style');
    styleTag.textContent = styles;
    document.head.appendChild(styleTag);

    // Close actions
    box.querySelector('.close-btn').addEventListener('click', () => overlay.remove());
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) overlay.remove();
    });

    // Auto-close only for success
    if (type === 'success') {
      setTimeout(() => {
        if (overlay.parentNode) overlay.remove();
      }, 8000);
    }
  }
});
