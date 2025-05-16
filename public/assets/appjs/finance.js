$(document).ready(function () {
  let tableFinance = null;

  function initTable() {
    tableFinance = $('#transactionsTable').DataTable({
      ajax: {
        url: '/api/transactions',
        data: function () {
          return {
            periode: $('#filter-periode').val(),
            type: $('#filter-type').val(),
            motif: $('#filter-motif').val(),
            libelle: $('#filter-libelle').val()
          };
        },
        dataSrc: 'data'
      },
      columns: [
        { data: 'id' },
        { data: 'date' },
        {
          data: 'type',
          render: function (type) {
            const badge = type === 'entrée' ? 'success' : 'danger';
            return `<span class="badge bg-${badge}">${type}</span>`;
          }
        },
        {
          data: 'montant',
          render: function (d) {
            return `${parseFloat(d || 0).toLocaleString()} FCFA`;
          }
        },
        { data: 'libelle' },
        { data: 'motif' },
        { data: 'description' },
        {
          data: null,
          render: function (row) {
            return `
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-secondary btn-print" data-id="${row.id}">
                  <i class="bi bi-printer"></i>
                </button>
                ${row.is_Erasable ? `
                  <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${row.id}">
                    <i class="bi bi-trash"></i>
                  </button>
                ` : ''}
              </div>
            `;
          },
          orderable: false
        }
      ],
      drawCallback: function () {
        updateTotals(tableFinance);
      },language: {
          url: '/api/DataTableFRJson'
        }
    });
  }

  // Mettre à jour les totaux
  function updateTotals(table) {
    if (!table) return;

    const data = table.rows().data();
    let totalEntrees = 0;
    let totalSorties = 0;

    data.each(row => {
      const montant = parseFloat(row.montant || 0);
      if (row.type === 'entrée') totalEntrees += montant;
      else totalSorties += montant;
    });

    const solde = totalEntrees - totalSorties;

    $('#total-entrees').text(`${totalEntrees.toLocaleString()} FCFA`);
    $('#total-sorties').text(`${totalSorties.toLocaleString()} FCFA`);
    $('#solde-net').text(`${solde} FCFA`);
  }

  function chargerStatistiques() {
    $.get('/api/transactions/stats', function (data) {
      $('#total-entrees').text(`${parseFloat(data.total_entrees || 0).toLocaleString()} FCFA`);
      $('#total-sorties').text(`${parseFloat(data.total_sorties || 0).toLocaleString()} FCFA`);
      $('#solde-net').text(`${parseFloat(data.solde_net || 0).toLocaleString()} FCFA`);
    });
  }

  $('#btnFiltrerTransactions').on('click', function () {
    if (tableFinance) tableFinance.ajax.reload();
    chargerStatistiques();
  });

  $('#formAddTransaction').on('submit', function (e) {
    e.preventDefault();

    const payload = {
      type: $('#type').val(),
      montant: parseFloat($('#montant').val()),
      libelle: $('#libelle').val(),
      motif: $('#motif').val(),
      description: $('#description').val()
    };

    if (!payload.type || !payload.montant || !payload.motif) {
      showToastModal({ message: 'Champs requis manquants', type: 'warning' });
      return;
    }

    $.ajax({
      url: '/api/transactions',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(payload),
      success: function (res) {
        $('#modalAddTransaction').modal('hide');
        $('#formAddTransaction')[0].reset();
        tableFinance.ajax.reload();
        chargerStatistiques();
        showToastModal({ message: res.message, type: 'success' });
      },
      error: function (err) {
        showToastModal({ message: err.responseJSON?.error || 'Erreur serveur', type: 'error' });
      }
    });
  });

  // Impression stylée d’une transaction
  $(document).on('click', '.btn-print', function () {
    const id = $(this).data('id');

    $.get(`/api/transactions/${id}`, function (t) {
      const html = `
        <html><head>
          <title>Reçu de Transaction</title>
          <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
          <style>
            body { font-size: 13px; padding: 20px; }
            .footer { position: fixed; bottom: 50px; width: 100%; text-align: center; font-size: 12px; color: #777; }
          </style>
        </head><body>
          <div style="position: relative; text-align: center; color: #007BFF; margin-bottom: 20px;">
            <h2 style="font-family: 'Georgia', serif; margin-bottom: 0;">AUTO BANAMBA</h2>
            <div style="font-weight: bold;">Kalilou TIGANA</div>
            <div>VENDEUR DES PIÈCES DE GROS PORTEURS</div>
            <div>Renault Actrs, Car Man etc...</div>
            <div style="font-size: 13px;">
              Tél : (+223) 66 16 88 03 / 50 30 01 31 / 66 29 62 03 / 76 16 88 03 <br>
              République du Mali
            </div>

            <!-- Logos flottants aléatoires -->
            <img src="/assets/img/logo_renault.png" style="position: absolute; top: 5px; left: 6px; height: 68px;">
            <img src="/assets/img/logo_bosch.png" style="position: absolute; top: 85px; left: 0px; height: 45px;">
            <img src="/assets/img/logo_mercedes.png" style="position: absolute; top: 20px; right: 15px; height: 65px;">
            <img src="/assets/img/logo_sachs.png" style="position: absolute; top: 80px; right: 15px; height: 40px;">
            <img src="/assets/img/camion.png" style="position: absolute; top: 15px; left: 80px; height: 82px;">
            <img src="/assets/img/tools.png" style="position: absolute; top: 20px; right: 90px; height: 48px;">
            <img src="/assets/img/logo_actros.png" style="position: absolute; top: 100px; right: 80px; height: 70px;">
          </div>

          <hr>
          <h5>Reçu de Transaction</h5>
          <p><strong>Date :</strong> ${t.date}</p>
          <p><strong>Type :</strong> ${t.type}</p>
          <p><strong>Montant :</strong> ${parseFloat(t.montant || 0).toLocaleString()} FCFA</p>
          <p><strong>Libellé :</strong> ${t.libelle || '-'}</p>
          <p><strong>Motif :</strong> ${t.motif}</p>
          <p><strong>Description :</strong> ${t.description || '-'}</p>

          <div class="d-flex justify-content-between mt-5">
            <div><strong>Cachet de l’entreprise</strong></div>
            <div><strong>Signature</strong></div>
          </div>

          <div class="footer">Merci pour votre confiance - Auto Banamba</div>
        </body></html>
      `;
      const win = window.open('', '', 'width=900,height=700');
      win.document.write(html);
      win.document.close();
      win.print();
    });
  });

  // Annulation des transactions libres
  $(document).on('click', '.btn-delete', function () {
    const id = $(this).data('id');
    if (!confirm('Annuler cette transaction ?')) return;

    $.ajax({
      url: `/api/transactions/${id}`,
      method: 'DELETE',
      success: function (res) {
        showToastModal({ message: res.message, type: 'success' });
        tableFinance.ajax.reload();
        chargerStatistiques();
      },
      error: function (res) {
        showToastModal({ message: res.responseJSON?.error || 'Suppression refusée', type: 'error' });
      }
    });
  });

  initTable();
  $('#filter-periode').daterangepicker({ locale: { format: 'YYYY-MM-DD' } });
  chargerStatistiques();
});
