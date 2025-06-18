$(document).ready(function () {
    const $table = $('#mouvementsTable').DataTable({
      ajax: {
        url: '/api/mouvements',
        dataSrc: '',
        data: function (d) {
          const [start, end] = $('#filtre-date').val().split(' - ');
          d.produit = $('#filtre-produit').val();
          d.type = $('#filtre-type').val();
          d.date_start = start;
          d.date_end = end;
        }
      },
      columns: [
        { data: 'id' },
        { data: 'date' },
        { data: 'produit' },
        {
          data: 'type',
          render: function (data) {
            const color = data === 'entrée' ? 'success' : 'danger';
            return `<span class="badge bg-${color}">${data}</span>`;
          }
        },
        { data: 'quantite' },
        { data: 'source' },
        { data: 'commentaire' }
      ],
      order: [[1, 'desc']],
      language: {
        url: '/api/DataTableFRJson'
      }
    });
  
    $('#filtreForm').on('submit', function (e) {
      e.preventDefault();
      $table.ajax.reload();
      updateStats();
    });
  
    function updateStats() {
      const [start, end] = $('#filtre-date').val().split(' - ');
      $.get('/api/mouvements/stats', {
        type: $('#filtre-type').val(),
        produit: $('#filtre-produit').val(),
        date_start: start,
        date_end: end
      }, function (data) {
        $('#stat-total').text(data.total);
        $('#stat-entrees').text(data.entrees);
        $('#stat-sorties').text(data.sorties);
      });
    }
  
    $('#btn-print-mouvements').on('click', function () {
      const printContent = $table.table().container().outerHTML;
      const win = window.open('', '_blank');
      win.document.write(`
        <html>
          <head>
            <title>Impression - Mouvements de Stock</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
            <style>
              body { padding: 20px; font-family: sans-serif; }
              table { width: 100%; border-collapse: collapse; font-size: 14px; }
              th, td { padding: 8px; border: 1px solid #ccc; text-align: left; }
              h3 { margin-bottom: 20px; }
            </style>
          </head>
          <body>
            <h3>Mouvements de Stock</h3>
            ${$('#mouvementsTable').parent().html()}
          </body>
        </html>
      `);
      win.document.close();
      win.print();
      setTimeout(() => win.close(), 500);
    });
  
    // Init du picker de dates
    $('#filtre-date').daterangepicker({
      locale: {
        format: 'YYYY-MM-DD',
        separator: ' - ',
        applyLabel: 'Appliquer',
        cancelLabel: 'Annuler',
        daysOfWeek: ['Di', 'Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa'],
        monthNames: [
          'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
          'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
        ],
        firstDay: 1
      },
      opens: 'left',
      startDate: moment().startOf('month'),
      endDate: moment().endOf('month')
    });
  
    // Premier chargement
    updateStats();
  });
  