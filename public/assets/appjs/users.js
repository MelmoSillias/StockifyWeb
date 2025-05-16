$(document).ready(function () {
    let table = null;
  
    function initTable() {
      table = $('#usersTable').DataTable({
        ajax: {
          url: '/api/users',
          data: function () {
            return {
              search: $('#searchUser').val(),
              role: $('#filterRole').val(),
              actif: $('#filterEtat').val()
            };
          },
          dataSrc: 'data'
        },
        columns: [
          { data: 'id' },
          { data: 'nom_utilisateur' },
          { data: 'nom_complet' },
          {
            data: 'roles',
            render: roles => roles.map(r =>
              `<span class="badge bg-primary me-1">${r.replace('ROLE_', '')}</span>`
            ).join('')
          },
          {
            data: 'actif',
            render: actif =>
              actif
                ? '<span class="badge bg-success">Actif</span>'
                : '<span class="badge bg-danger">Inactif</span>'
          },
          {
            data: 'id',
            render: id => `
              <button class="btn btn-sm btn-secondary toggle-activation" data-id="${id}">
                <i class="bi bi-power"></i>
              </button>
              <button class="btn btn-sm btn-danger delete-user" data-id="${id}">
                <i class="bi bi-trash"></i>
              </button>
            `,
            orderable: false
          }
        ],language: {
          url: '/api/DataTableFRJson'
        }
      });
    }
  
    function reloadTable() {
      if (table) {
        table.ajax.reload();
      }
    }
  
    $('#filterRole, #filterEtat').on('change', () => {
      reloadTable();
    });

    $('#searchUser').on('input', function () {
      table.search(this.value).draw(); // utilise le moteur natif de DataTables
    });
  
    $('#formAddUser').on('submit', function (e) {
      e.preventDefault();
      const payload = {
        nom_utilisateur: $('#username').val(),
        nom_complet: $('#fullname').val(),
        password: $('#password').val(),
        roles: [$('#roles').val()],
        actif: $('#etat').is(':checked')
      };
  
      $.ajax({
        url: '/api/users',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(payload),
        success: () => {
          $('#modalAddUser').modal('hide');
          $('#formAddUser')[0].reset();
          reloadTable();
          showToastModal({ message: 'Utilisateur ajouté', type: 'success' });
        },
        error: err => {
          showToastModal({ message: err.responseJSON?.error || 'Erreur serveur', type: 'error' });
        }
      });
    });
  
    $(document).on('click', '.toggle-activation', function () {
      const userId = $(this).data('id');
      $.post(`/api/users/${userId}/toggle`, {}, () => {
        reloadTable();
        showToastModal({ message: 'État du compte mis à jour', type: 'success' });
      }).fail(() => {
        showToastModal({ message: 'Erreur de mise à jour', type: 'error' });
      });
    });
  
    $(document).on('click', '.delete-user', function () {
      const userId = $(this).data('id');
      $('#deleteUserId').val(userId);
      $('#modalDeleteUser').modal('show');
    });
  
    $('#confirmDeleteUser').on('click', function () {
      const userId = $('#deleteUserId').val();
      $.ajax({
        url: `/api/users/${userId}`,
        method: 'DELETE',
        success: () => {
          $('#modalDeleteUser').modal('hide');
          reloadTable();
          showToastModal({ message: 'Utilisateur supprimé', type: 'success' });
        },
        error: () => {
          showToastModal({ message: 'Erreur lors de la suppression', type: 'error' });
        }
      });
    });
  
    initTable();
  });

  