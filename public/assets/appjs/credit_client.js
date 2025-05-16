$(document).ready(function () {
    let tableCredits;
    let creditActuel = null;
  
    const initDataTable = () => {
      tableCredits = $('#creditsTable').DataTable({
        ajax: {
          url: '/api/credits',
          data: () => ({
            periode: $('#filtre-periode').val(),
            client: $('#filtre-client').val(),
            statut: $('#filtre-statut').val()
          }),
          dataSrc: 'data'
        },
        columns: [
          {
            data: null,
            className: 'details-control',
            orderable: false,
            defaultContent: '<button class="btn btn-sm btn-outline-primary"><i class="bi bi-chevron-down"></i></button>'
          }, 
          { data: 'id' },
          { data: 'vente_id' },
          { data: 'client_nom' },
          {
            data: 'montant_total',
            render: d => `<span class="badge bg-primary">${d.toFixed(0)} FCFA</span>`
          },
          {
            data: 'montant_restant',
            render: d => `<span class="badge bg-warning text-dark">${d.toFixed(0)} FCFA</span>`
          },
          {
            data: 'statut',
            render: s => {
              const styles = {
                impayé: 'danger',
                partiel: 'warning text-dark',
                payé: 'success'
              };
              return `<span class="badge bg-${styles[s] || 'secondary'}">${s}</span>`;
            }
          },
          {
            data: null,
            render: (_, __, row) => `
              <div class="d-flex gap-2">
                <button class="btn btn-sm btn-success btn-payer" data-id="${row.id}" data-restant="${row.montant_restant}">
                  <i class="bi bi-wallet2"></i>
                </button>
                <button class="btn btn-sm btn-secondary btn-print" data-id="${row.id}">
                  <i class="bi bi-printer"></i>
                </button>
              </div>
            `,
            orderable: false
          }
        ],language: {
          url: '/api/DataTableFRJson'
        }
      });
    };

    $('#creditsTable tbody').on('click', 'td.details-control', function () {
  const tr = $(this).closest('tr');
  const row = tableCredits.row(tr);

  if (row.child.isShown()) {
    row.child.hide();
    tr.removeClass('shown');
  } else {
    const credit = row.data();
    afficherDetailsCredit(row, credit.id);
    tr.addClass('shown');
  }
});

function afficherDetailsCredit(row, creditId) {
  $.get(`/api/credits/${creditId}/details`, function (res) {
    let html = `<div class="row">
      <div class="col-md-6">
        <h6>Paiements effectués</h6>
        <table class="table table-sm table-bordered">
          <thead><tr><th>Date</th><th>Montant</th><th></th></tr></thead><tbody>`;

    res.paiements.forEach(p => {
      html += `
        <tr>
          <td>${p.date}</td>
          <td>${p.montant} FCFA</td>
          <td>
            <button class="btn btn-sm btn-outline-danger btn-delete-paiement" data-id="${p.id}">
              <i class="bi bi-x-circle"></i>
            </button>
          </td>
        </tr>`;
    });

    html += `</tbody></table></div>`;

    html += `<div class="col-md-6">
        <h6>Détail de la vente</h6>
        <table class="table table-sm table-bordered">
          <thead><tr><th>Produit</th><th>Qté</th><th>PU</th><th>Total</th></tr></thead><tbody>`;

    res.vente.forEach(l => {
      html += `<tr>
        <td>${l.produit}</td>
        <td>${l.quantite}</td>
        <td>${l.pu} FCFA</td>
        <td>${(l.quantite * l.pu).toFixed(0)} FCFA</td>
      </tr>`;
    });

    html += `</tbody></table></div></div>`;

    row.child(html).show();
  });
} 
    $('#btn-filtrer-credits').on('click', function () {
      tableCredits.ajax.reload();
      chargerStatistiquesCredits();
    });
    // Paiement
    $(document).on('click', '.btn-payer', function () {
      const id = $(this).data('id');
      const restant = $(this).data('restant');
      creditActuel = id;
  
      $('#creditId').val(id);
      $('#montantAPayer').val('');
      $('#creditRestantInfo').text(`Reste à payer : ${restant} FCFA`);
      $('#montantAPayer').attr('max', restant);
      $('#modalPaiementCredit').modal('show');
    });
  
    $('#formPaiementCredit').on('submit', function (e) {
      e.preventDefault();
  
      const montant = parseFloat($('#montantAPayer').val());
      const id = $('#creditId').val();
  
      if (!montant || montant <= 0) {
        showToastModal({ message: 'Montant invalide', type: 'warning' });
        return;
      }
  
      $.post(`/api/credits/${id}/payer`, { montant: montant }, function (res) {
        $('#modalPaiementCredit').modal('hide');
        showToastModal({ message: res.message, type: 'success' });
        tableCredits.ajax.reload();
      }).fail(res => {
        showToastModal({
          message: res.responseJSON?.error || 'Erreur',
          type: 'error'
        });
      });
    });
  
    // Impression d’un crédit (ex : reçu)
   $(document).on('click', '.btn-print', function () {
  const id = $(this).data('id');

  $.get(`/api/credits/${id}/details`, function (res) {
    const now = new Date().toLocaleDateString();

    let paiementsRows = '';
    res.paiements.forEach(p => {
      paiementsRows += `
        <tr>
          <td>${p.date}</td>
          <td>${p.montant.toFixed(0)} FCFA</td>
        </tr>`;
    });

    let venteRows = '';
    res.vente.forEach(v => {
      const total = v.quantite * v.pu;
      venteRows += `
        <tr>
          <td>${v.produit}</td>
          <td>${v.quantite}</td>
          <td>${v.pu.toFixed(0)} FCFA</td>
          <td>${total.toFixed(0)} FCFA</td>
        </tr>`;
    });

    const html = `
      <html>
        <head>
          <title>Impression Crédit Client</title>
          <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
          <style>
            body { padding: 20px; font-size: 13px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { padding: 4px; border: 1px solid #ccc; }
            .footer {
              position: fixed;
              bottom: 50px;
              left: 0;
              width: 100%;
              text-align: center;
              font-size: 12px;
              color: #888;
            }
          </style>
        </head>
        <body>
          <div style="position: relative; text-align: center; color: #007BFF; margin-bottom: 20px;">
            <h2 style="font-family: 'Georgia', serif; margin-bottom: 0;">AUTO BANAMBA</h2>
            <div style="font-weight: bold;">Kalilou TIGANA</div>
            <div>VENDEUR DES PIÈCES DE GROS PORTEURS</div>
            <div>Renault Actrs, Car Man etc...</div>
            <div style="font-size: 13px;">
              Tél : (+223) 66 16 88 03 / 50 30 01 31 / 66 29 62 03 / 76 16 88 03 <br>
              République du Mali
            </div>

            <img src="/assets/img/logo_renault.png" style="position: absolute; top: 5px; left: 6px; height: 68px;">
            <img src="/assets/img/logo_bosch.png" style="position: absolute; top: 85px; left: 0px; height: 45px;">
            <img src="/assets/img/logo_mercedes.png" style="position: absolute; top: 20px; right: 15px; height: 65px;">
            <img src="/assets/img/logo_sachs.png" style="position: absolute; top: 80px; right: 15px; height: 40px;">
            <img src="/assets/img/camion.png" style="position: absolute; top: 15px; left: 80px; height: 82px;">
            <img src="/assets/img/tools.png" style="position: absolute; top: 20px; right: 90px; height: 48px;">
            <img src="/assets/img/logo_actros.png" style="position: absolute; top: 100px; right: 80px; height: 70px;">
          </div>

          <div class="d-flex justify-content-between mb-3">
            <div><strong>Client :</strong> ${res.client_nom || '-'}</div>
            <div><strong>Date :</strong> ${now}</div>
          </div>
          <div class="mb-3"><strong>Reste à payer :</strong> ${res.montant_restant?.toLocaleString() || '0'} FCFA</div>

          <h6>Paiements</h6>
          <table class="table table-sm table-bordered">
            <thead><tr><th>Date</th><th>Montant</th></tr></thead>
            <tbody>${paiementsRows || '<tr><td colspan="2">Aucun paiement</td></tr>'}</tbody>
          </table>

          <h6 class="mt-4">Détail de la vente</h6>
          <table class="table table-sm table-bordered">
            <thead><tr><th>Produit</th><th>Qté</th><th>PU</th><th>Total</th></tr></thead>
            <tbody>${venteRows}</tbody>
          </table>

          <div class="d-flex justify-content-between mt-5">
            <div><strong>Cachet de l’entreprise</strong></div>
            <div><strong>Signature client</strong></div>
          </div>

          <div class="footer">Merci pour votre confiance - Auto Banamba</div>
        </body>
      </html>
    `;

    const win = window.open('', '_blank', 'width=900,height=800');
    win.document.write(html);
    win.document.close();
    win.print();
  });
});

  
    // Impression de la liste
    $('#btnPrintCredits').on('click', function () {
  const content = document.querySelector('.print-area').innerHTML;

  const win = window.open('', '', 'width=1000,height=800');
  win.document.write(`
    <html>
      <head>
        <title>Impression des Crédits</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <style>
          body { padding: 20px; font-size: 12px; }
          .table th, .table td { padding: 4px !important; }
        </style>
      </head>
      <body>
        <h4 class="text-center mb-4">Liste des Crédits Clients</h4>
        ${content}
      </body>
    </html>
  `);
  win.document.close();
  win.print();
});

  
    // Initialisation
    initDataTable();
    $('#filtre-periode').daterangepicker({ locale: { format: 'DD-MM-YYYY' } });

    $(document).on('click', '.btn-delete-paiement', function () {
  const id = $(this).data('id');

  if (!confirm('Annuler ce paiement ?')) return;

  $.ajax({
    url: `/api/paiements/${id}`,
    method: 'DELETE',
    success: function (res) {
      showToastModal({ message: res.message, type: 'success' });
      tableCredits.ajax.reload(); // recharge table principale
       chargerStatistiquesCredits();
    },
    error: function () {
      showToastModal({ message: 'Erreur lors de la suppression', type: 'error' });
    }
  });
});

function chargerStatistiquesCredits() {
  $.get('/api/credits/stats', {
    periode: $('#filtre-periode').val(),
    client: $('#filtre-client').val()
  }, function (data) {
    $('#stat-nb-total').text(data.nb_total);
    $('#stat-montant-total').text(data.montant_total.toLocaleString());
    $('#stat-nb-payes').text(data.nb_payes);
    $('#stat-montant-payes').text(data.montant_payes.toLocaleString());
    $('#stat-nb-partiels').text(data.nb_partiels);
    $('#stat-montant-partiels').text(data.montant_partiels.toLocaleString());
    $('#stat-nb-impayes').text(data.nb_impayes);
    $('#stat-montant-impayes').text(data.montant_impayes.toLocaleString());
    $('#stat-recette').text(data.recette.toLocaleString());
  });
}

chargerStatistiquesCredits();


  });
  