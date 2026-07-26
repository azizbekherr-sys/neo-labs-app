(function () {
  'use strict';

  var ckeditorPromise;
  var ckeditorUrl = 'https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js';

  function loadEditor() {
    if (window.ClassicEditor) return Promise.resolve(window.ClassicEditor);
    if (ckeditorPromise) return ckeditorPromise;
    ckeditorPromise = new Promise(function (resolve, reject) {
      var script = document.createElement('script');
      script.src = ckeditorUrl;
      script.onload = function () { resolve(window.ClassicEditor); };
      script.onerror = reject;
      document.head.appendChild(script);
    });
    return ckeditorPromise;
  }

  function initializeEditors(scope) {
    var editors = (scope || document).querySelectorAll('textarea[data-editor]:not([data-editor-ready])');
    if (!editors.length) return;
    loadEditor().then(function (ClassicEditor) {
      editors.forEach(function (textarea) {
        if (textarea.closest('.tab-pane') && !textarea.closest('.tab-pane').classList.contains('active')) return;
        textarea.setAttribute('data-editor-ready', 'loading');
        ClassicEditor.create(textarea, {
          toolbar: ['bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'undo', 'redo']
        }).then(function (editor) {
          textarea._adminEditor = editor;
          textarea.setAttribute('data-editor-ready', 'true');
        }).catch(function () {
          textarea.removeAttribute('data-editor-ready');
        });
      });
    });
  }

  function initializeSalesMode(group) {
    var select = group.querySelector('[data-sales-mode]');
    if (!select) return;
    var update = function () {
      group.querySelectorAll('[data-sales-panel]').forEach(function (panel) {
        var active = panel.getAttribute('data-sales-panel') === select.value;
        panel.hidden = !active;
        panel.querySelectorAll('input,select,textarea').forEach(function (field) {
          field.disabled = !active;
        });
      });
    };
    select.addEventListener('change', update);
    update();
  }

  function initializeSeoOverride(group) {
    var toggle = group.querySelector('[data-seo-override]');
    var panel = group.querySelector('[data-seo-panel]');
    if (!toggle || !panel) return;
    var update = function () {
      panel.hidden = !toggle.checked;
      panel.querySelectorAll('input,select,textarea').forEach(function (field) {
        field.disabled = !toggle.checked;
      });
    };
    toggle.addEventListener('change', update);
    update();
  }

  function initializeCharacterCount(field) {
    var wrapper = field.parentElement;
    var output = wrapper && wrapper.querySelector('[data-character-output]');
    if (!output) return;
    var update = function () {
      output.textContent = field.value.length + '/' + (field.maxLength > 0 ? field.maxLength : '∞');
    };
    field.addEventListener('input', update);
    update();
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.is-invalid').forEach(function (field, index) {
      field.setAttribute('aria-invalid', 'true');
      var feedback = field.parentElement && field.parentElement.querySelector('.invalid-feedback');
      if (feedback) {
        if (!feedback.id) feedback.id = 'admin-field-error-' + index;
        field.setAttribute('aria-describedby', feedback.id);
      }
    });

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
      new bootstrap.Tooltip(element);
    });

    var deleteModalElement = document.getElementById('deleteConfirmModal');
    var deleteModal = deleteModalElement ? new bootstrap.Modal(deleteModalElement) : null;
    var pendingDeleteForm = null;
    document.querySelectorAll('[data-confirm-delete]').forEach(function (button) {
      button.addEventListener('click', function () {
        pendingDeleteForm = document.getElementById(button.getAttribute('data-form-id'));
        var label = button.getAttribute('data-object-label') || 'Tanlangan obyekt';
        document.getElementById('deleteConfirmText').textContent = '“' + label + '” butunlay o‘chiriladi. Bu amalni bekor qilib bo‘lmaydi.';
        deleteModal.show();
      });
    });
    var confirmDeleteButton = document.getElementById('confirmDeleteButton');
    if (confirmDeleteButton) confirmDeleteButton.addEventListener('click', function () {
      if (pendingDeleteForm) pendingDeleteForm.submit();
    });

    document.querySelectorAll('.admin-file-input').forEach(function (input) {
      input.addEventListener('change', function () {
        var output = document.getElementById(input.getAttribute('aria-describedby'));
        if (!output) return;
        if (!input.files.length) { output.textContent = 'Fayl tanlanmagan'; return; }
        output.textContent = Array.from(input.files).map(function (file) {
          return file.name + ' (' + Math.max(1, Math.round(file.size / 1024)) + ' KB)';
        }).join(', ');
      });
    });

    document.querySelectorAll('[data-sales-mode-group]').forEach(initializeSalesMode);
    document.querySelectorAll('[data-seo-override-group]').forEach(initializeSeoOverride);
    document.querySelectorAll('[data-character-count]').forEach(initializeCharacterCount);

    document.querySelectorAll('textarea[data-editor]').forEach(function (editor) {
      if (!editor.closest('.modal')) initializeEditors(editor.parentElement);
    });

    document.querySelectorAll('[data-editor-modal]').forEach(function (modal) {
      modal.addEventListener('shown.bs.modal', function () { initializeEditors(modal); });
      modal.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (tab) {
        tab.addEventListener('shown.bs.tab', function (event) {
          var pane = document.querySelector(event.target.getAttribute('data-bs-target'));
          initializeEditors(pane);
        });
      });
    });

    var modalId = document.body.getAttribute('data-open-modal');
    if (modalId) {
      var modalElement = document.getElementById(modalId);
      if (modalElement) {
        var invalid = modalElement.querySelector('.is-invalid, [aria-invalid="true"]');
        if (invalid && invalid.closest('.tab-pane')) {
          var tabButton = modalElement.querySelector('[data-bs-target="#' + invalid.closest('.tab-pane').id + '"]');
          if (tabButton) bootstrap.Tab.getOrCreateInstance(tabButton).show();
        }
        modalElement.addEventListener('shown.bs.modal', function () {
          (invalid || modalElement.querySelector('input,textarea,select,button')).focus();
          if (invalid) window.setTimeout(function () { invalid.focus(); }, 300);
        }, { once: true });
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
      }
    }
  });
})();
