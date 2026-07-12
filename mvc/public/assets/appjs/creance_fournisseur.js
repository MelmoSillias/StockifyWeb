
$(document).ready(function () {
  let tableCreances;
  let creanceEnCours = null;

  function initTable() {
    tableCreances = $('#creancesTable').DataTable({
      ajax: {
        url: '/api/creances',
        data: () => ({
          periode: $('#filtre-periode').val(),
          fournisseur: $('#filtre-fournisseur').val(),
          statut: $('#filtre-statut').val()
        }),
        dataSrc: 'data'
      },
      columns: [
        {
          className: 'details-control',
          orderable: false,
          data: null,
          defaultContent: '<i class="bi bi-caret-down-square-fill"></i>',
          width: '20px'
        },
        { data: 'id' },
        { data: 'fournisseur' },
        { data: 'date' },
        { data: 'devise' },
        {
          data: 'montant_total',
          render: (data, _, row) => `${parseFloat(data).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${row.devise}`
        },
        {
          data: 'montant_restant',
          render: (data, _, row) => `${parseFloat(data).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${row.devise}`
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
                <i class="bi bi-cash-coin"></i>
              </button>
              <button class="btn btn-sm btn-secondary btn-print" data-id="${row.id}">
                <i class="bi bi-printer"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger btn-delete-creance" data-id="${row.id}">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          `,
          orderable: false
        }
      ],language: {
          url: '/api/DataTableFRJson'
        }
    });
  }

  $('#btn-filtrer-creances').on('click', () => {
    tableCreances.ajax.reload();
    chargerStats();
  });

  function chargerStats() {
    $.get('/api/creances/stats', {
      periode: $('#filtre-periode').val(),
      fournisseur: $('#filtre-fournisseur').val()
    }, function (data) {
      $('#stat-creance-total').text(data.total);
      $('#stat-creance-payees').text(data.payees);
      $('#stat-creance-restantes').text(data.restantes);
    });
  }

  $('#btnSubmitCreanceForm').on('click', function (e) {
    e.preventDefault();
    const fournisseur = $('#fournisseur').val();
    const montant = $('#montant_devise').val();
    const devise = $('#devise').val();
    const date = $('#date').val();

    if (!fournisseur || !montant || !devise || !date) {
      showToastModal({ message: 'Veillez rensigner tout les champs.', type: 'warning' });
      return;
    }

    const data = {
      fournisseur,
      montant_devise: parseFloat(montant),
      devise,
      date,
    };

    $.post('/api/creances/create', data, function () {
      showToastModal({ message: 'Créance enregistrée', type: 'success' });
      $('#formAjoutCreanceFournisseur')[0].reset();
      tableCreances.ajax.reload();
      chargerStats();
    });
  });

$('#montantFCFA, #tauxChange').on('input', function () {
  const montantFCFA = parseFloat($('#montantFCFA').val()) || 0;
  const taux = parseFloat($('#tauxChange').val()) || 0;
  const montantDevise = taux > 0 ? (montantFCFA / taux) : 0;

  $('#montantDevise').val(montantDevise > 0 ? `${montantDevise.toFixed(2)}` : '');

  const maxDevise = parseFloat($('#creanceRestantInfo').data('restant')) || 0;
  if (montantDevise > maxDevise) {
    $('#montantDevise').addClass('is-invalid');
  } else {
    $('#montantDevise').removeClass('is-invalid');
  }
});




  $(document).on('click', '.btn-payer', function () {
 
    const id = $(this).data('id');
    const restant = $(this).data('restant');
    creanceEnCours = id;
    $('#creanceId').val(id);
    $('#montantPaye').val('');
    $('#tauxChange').val('');
    const restantDevise = $(this).data('restant');
    $('#creanceRestantInfo').text(`Montant restant : ${restantDevise} (devise)`);
    $('#creanceRestantInfo').data('restant', restantDevise);
    $('#modalPaiementCreance').modal('show');
  });

  $(document).on('click', '.btn-print', function () { 
  const id = $(this).data('id');

  $.get(`/api/creances/${id}/details`, function (res) {
    let rows = '';
    res.paiements.forEach(p => {
      rows += `<tr>
        <td>${p.date}</td>
        <td>${p.montant_devise.toFixed(2)}</td>
        <td>${p.taux}</td>
        <td>${p.montant_fcfa.toFixed(0)} FCFA</td>
      </tr>`;
    });

    const now = new Date().toLocaleDateString();

    const html = `
    <html><head>
      <title>Reçu Créance Fournisseur</title>
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
      <style>
        body { padding: 20px; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 5px; border: 1px solid #ccc; }
        .footer { position: fixed; bottom: 50px; width: 100%; text-align: center; font-size: 12px; color: #777; }
      </style>
    </head>
    <body>
      <div style="position: relative; text-align: center; color: #007BFF; margin-bottom: 20px;">
        <h2 style="font-family: 'Georgia', serif; margin-bottom: 0;">AUTO BANAMBA</h2>
        <div style="font-weight: bold;">Kalilou TIGANA</div>
        <div>VENDEUR DES PIÈCES DE GROS PORTEURS</div>
        <div>Renault Actrs, Car Man etc...</div>
        <div style="font-size: 13px;">
          Tél : (+223) 66 16 88 03 / 50 30 01 31 / 66 29 62 03 / 76 16 88 03<br>
          République du Mali
        </div>
        <img src="/assets/img/logo_renault.png" style="position: absolute; top: 5px; left: 6px; height: 68px;">
        <img src="/assets/img/logo_bosch.png" style="position: absolute; top: 85px; left: 0px; height: 45px;">
        <img src="/assets/img/logo_mercedes.png" style="position: absolute; top: 20px; right: 15px; height: 65px;">
        <img src="/assets/img/sachs.png" style="position: absolute; top: 80px; right: 15px; height: 40px;">
        <img src="/assets/img/camion.png" style="position: absolute; top: 15px; left: 80px; height: 82px;">
        <img src="/assets/img/tools.png" style="position: absolute; top: 20px; right: 90px; height: 48px;">
        <img src="/assets/img/logo_actros.png" style="position: absolute; top: 100px; right: 80px; height: 70px;">
      </div>

      <div class="d-flex justify-content-between mb-3">
        <div><strong>Fournisseur :</strong> ${res.fournisseur || '---'}</div>
        <div><strong>Date :</strong> ${res.date}</div>
      </div>

      <div><strong>Montant restant :</strong> ${(res.montant_restant || 0).toLocaleString()} ${res.devise}</div>

      <h6 class="mt-4">Paiements effectués</h6>
      <table class="table table-sm table-bordered">
        <thead><tr><th>Date</th><th>Montant</th><th>Taux</th><th>Montant FCFA</th></tr></thead>
        <tbody>${rows || '<tr><td colspan="4">Aucun paiement</td></tr>'}</tbody>
      </table>

      <div class="d-flex justify-content-between mt-5">
        <div><strong>Cachet de l’entreprise</strong></div>
        <div><strong>Signature fournisseur</strong></div>
      </div>

      <div class="footer">Merci pour votre confiance - Auto Banamba</div>
    </body></html>`;

    const printWindow = window.open('', '_blank');
        printWindow.document.open();
        printWindow.document.write(html);
        printWindow.document.close();
      
        printWindow.onload = function () {
          printWindow.focus();
          printWindow.print();
          setTimeout(() => printWindow.close(), 500);
        };
  });
});




  $('#formPaiementCreance').on('submit', function (e) {
    e.preventDefault();
    const montant = parseFloat($('#montantFCFA').val());
    const taux = parseFloat($('#tauxChange').val());
    const id = $('#creanceId').val();

    const montantDevise = parseFloat($('#montantDevise').val()) || 0;
    const montantMax = parseFloat($('#creanceRestantInfo').data('restant')) || 0;

if (montantDevise > montantMax) {
  showToastModal({ message: 'Le paiement dépasse le montant restant.', type: 'warning' });
  return;
}


    if (!montant || !taux) {
      showToastModal({ message: 'Montant ou taux invalide', type: 'warning' });
      return;
    }

    $.post(`/api/creances/${id}/payer`, { montant, taux }, function () {
      $('#modalPaiementCreance').modal('hide');
      showToastModal({ message: 'Paiement enregistré', type: 'success' });
      tableCreances.ajax.reload();
      chargerStats();
    });
  });

  $('#creancesTable tbody').on('click', 'td.details-control', function () {
    const tr = $(this).closest('tr');
    const row = tableCreances.row(tr);

    if (row.child.isShown()) {
      row.child.hide();
      tr.removeClass('shown');
    } else {
      $.get(`/api/creances/${row.data().id}/details`, function (res) {
        let html = `
          <table class="table table-sm table-bordered mt-2">
            <thead><tr><th>Date</th><th>Montant (devise)</th><th>Taux</th><th>FCFA</th><th></th></tr></thead><tbody>`;
        res.paiements.forEach(p => {
          html += `<tr>
            <td>${p.date}</td>
            <td>${p.montant_devise}</td>
            <td>${p.taux}</td>
            <td>${p.montant_fcfa}</td>
            <td><button class="btn btn-sm btn-outline-danger btn-delete-paiement" data-id="${p.id}">
              <i class="bi bi-x-circle"></i></button></td>
          </tr>`;
        });
        html += '</tbody></table>';
        row.child(html).show();
        tr.addClass('shown');
      });
    }
  });

  $(document).on('click', '.btn-delete-paiement', function () {
    const id = $(this).data('id');
    if (!confirm('Annuler ce paiement ?')) return;

    $.ajax({
      url: `/api/paiements-fournisseur/${id}`,
      method: 'DELETE',
      success: function () {
        showToastModal({ message: 'Paiement supprimé', type: 'success' });
        tableCreances.ajax.reload();
        chargerStats();
      }
    });
  });

  $(document).on('click', '.btn-delete-creance', function () {
    const id = $(this).data('id');
    if (!confirm('Supprimer cette créance fournisseur ?')) return;

    $.ajax({
      url: `/api/creances/${id}`,
      method: 'DELETE',
      success: function () {
        showToastModal({ message: 'Créance supprimée', type: 'success' });
        tableCreances.ajax.reload();
        chargerStats();
      }
    });
  });

  $('#btnPrintCreances').on('click', function () {
  const filtrePeriode = $('#filtre-periode').val();
  const filtreFournisseur = $('#filtre-fournisseur').val();
  const filtreStatut = $('#filtre-statut option:selected').text();

  const tableClone = $('#creancesTable').clone();
  tableClone.find('thead th:first-child, tbody td:first-child').remove(); // ⛔ détails
  tableClone.find('thead th:last-child, tbody td:last-child').remove();   // ⛔ actions

  tableClone.find('tbody tr').each(function () {
    $(this).find('td:first-child').remove();
    $(this).find('td:last-child').remove();
  });

  const statsClone = $('#statsCreanceFournisseur').clone();

  const html = `
    <html><head>
      <title>Liste des Créances</title>
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
      <style>
        body { font-size: 13px; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px; border: 1px solid #ccc; font-size: 12px; }
        h4 { margin-bottom: 20px; }
      </style>
    </head>
    <body>
      <h4 class="text-center">Liste des Créances Fournisseurs</h4>
      <p><strong>Période :</strong> ${filtrePeriode || '---'} |
         <strong>Fournisseur :</strong> ${filtreFournisseur || '---'} |
         <strong>Statut :</strong> ${filtreStatut || '---'}</p>

      ${tableClone.prop('outerHTML')}

      <hr>
      <h5>Statistiques</h5>
      ${statsClone.prop('outerHTML')}
    </body></html>
  `;

  const printWindow = window.open('', '_blank');
        printWindow.document.open();
        printWindow.document.write(html);
        printWindow.document.close();
      
        printWindow.onload = function () {
          printWindow.focus();
          printWindow.print();
          setTimeout(() => printWindow.close(), 500);
        };
});


  initTable();
  $('#filtre-periode').daterangepicker({ locale: { format: 'YYYY-MM-DD' } });
  chargerStats();
});
