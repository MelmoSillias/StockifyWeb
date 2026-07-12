$(document).ready(function () {
    let tableProduits;
  
    // Init table
    function initTable() {
      tableProduits =$('#produitsTable').DataTable({
        ajax: { url: '/api/produits', dataSrc: '' },
        columns: [
          { data: 'id' },
          { data: 'nom' },
          { data: 'reference' },
          { data: 'categorie' },
          { data: 'stock_actuel' },
          { data: 'pme', render: d => d + ' FCFA' },
          {
            data: null,
            render: r => (r.pme * r.stock_actuel) + ' FCFA'
          },
          {
            data: 'prix_de_vente', render: d => d + ' FCFA'
          },
          {
            data: null,
            render: row => `
              <button class="btn btn-sm btn-success btn-add" data-id="${row.id}"><i class="bi bi-plus"></i></button>
              <button class="btn btn-sm btn-primary btn-edit" data-id="${row.id}"><i class="bi bi-pencil"></i></button>
              <button class="btn btn-sm btn-danger btn-delete" data-id="${row.id}"><i class="bi bi-trash"></i></button>
              <button class="btn btn-sm btn-secondary btn-lot" data-id="${row.id}"><i class="bi bi-box-seam"></i></button>
            `
          }
        ],
        rowCallback: function (row, data) {
          if (data.seuil_alerte !== null && data.stock_actuel <= data.seuil_alerte) {
            $(row).addClass('table-danger');
          }
        },
        language: {
          url: '/api/DataTableFRJson'
        }
      });
    }
  
    // Stats
    function updateStats() {
      $.get('/api/produits/stats', function (data) {
        $('#stat-total-produits').text(data.totalProduits);
        $('#stat-total-stock').text(data.stockTotal);
        $('#stat-valeur-estimee').text(data.valeurTotale + ' FCFA');
      });
    }
  
    // Reset form produit
    function resetProduitForm() {
      $('#produitForm')[0].reset();
      $('#produit-id').val('');
    }
  
    // Ouvrir modal produit
    $('#btn-add-product').click(function () {
      resetProduitForm();
      $('#produitModalLabel').text('Ajouter un produit');
      $('#produitModal').modal('show');
    });
  
    // Soumettre produit
    $('#produitForm').submit(function (e) {
      e.preventDefault();
  
      // Générer une référence unique si le champ est vide
      let reference = $('#produit-reference').val();
      if (!reference) {
        // Exemple: REF-YYYYMMDD-HHMMSS-rand
        const now = new Date();
        const pad = n => n.toString().padStart(2, '0');
        const dateStr = `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}`;
        const timeStr = `${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}`;
        const rand = Math.floor(Math.random() * 1000);
        reference = `REF-${dateStr}-${timeStr}-${rand}`;
      }

      const payload = {
        id: $('#produit-id').val(),
        nom: $('#produit-nom').val(),
        reference: reference,
        categorie: $('#produit-categorie').val(),
        description: $('#produit-description').val(),
        seuil_alerte: $('#produit-seuil').val(),
        actif: $('#produit-actif').is(':checked'),
        prix_de_vente: $('#produit-prix-de-vente').val(),
      };
  
      $.ajax({
        url: '/api/produits/create',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(payload),
        success: function () {
          $('#produitModal').modal('hide');
          tableProduits.ajax.reload();
          updateStats();
          showToastModal({ message: 'Produit enregistré', type: 'success' });
        },
        error: function () {
          showToastModal({ message: 'Erreur lors de l’enregistrement', type: 'error' });
        }
      });
    });
  
    // Modifier produit
$('#produitsTable').on('click', '.btn-edit', function () {
  const id = $(this).data('id');
  $.get('/api/produits/' + id, function (p) {
    $('#modProduit-id').val(p.id);
    $('#modProduit-nom').val(p.nom);
    $('#modProduit-reference').val(p.reference);
    $('#modProduit-categorie').val(p.categorie);
    $('#modProduit-description').val(p.description);
    $('#modProduit-prix-de-vente').val(p.prix_de_vente);
    $('#modProduit-seuil').val(p.seuil_alerte);
    $('#modProduit-actif').prop('checked', p.actif);
    $('#modProduitModalLabel').text('Modifier le produit');
    $('#modModProduitModal').modal('show');
  });
});
    // Soumettre produit modifié
    $('#ModProSubmitBtn').on('click', function (e) {
      e.preventDefault();
      
      const payload = {
        id: $('#modProduit-id').val(),
        nom: $('#modProduit-nom').val(),
        reference: $('#modProduit-reference').val(),
        categorie: $('#modProduit-categorie').val(),
        description: $('#modProduit-description').val(),
        seuil_alerte: $('#modProduit-seuil').val(),
        actif: $('#modProduit-actif').is(':checked'),
        prix_de_vente: $('#modProduit-prix-de-vente').val(),
      };

      $.ajax({
        url: '/api/produits/' + payload.id,
        method: 'PUT',
        contentType: 'application/json',
        data: JSON.stringify(payload),
        success: function () {
          $('#modModProduitModal').modal('hide');
          tableProduits.ajax.reload();
          updateStats();
          showToastModal({ message: 'Produit modifié', type: 'success' });
        },
        error: function () {
          showToastModal({ message: 'Erreur lors de la modification', type: 'error' });
        }
      });
    });
    
  
    // Supprimer produit
    $('#produitsTable').on('click', '.btn-delete', function () {
      const id = $(this).data('id');
      if (!confirm("Supprimer ce produit ?")) return;
  
      $.ajax({
        url: '/api/produits/' + id,
        method: 'DELETE',
        success: function () {
          tableProduits.ajax.reload();
          updateStats();
          showToastModal({ message: 'Produit supprimé', type: 'success' });
        },
        error: function () {
          showToastModal({ message: 'Erreur de suppression', type: 'error' });
        }
      });
    });
  
    // Ouvrir modal lot
    $('#produitsTable').on('click', '.btn-add', function () {
      const produitId = $(this).data('id');
      $('#lot-produit-id').val(produitId);
      $('#lotForm')[0].reset();
      $('#stockModal').modal('show');
    });
  
    // Init datepicker
    $('#lot-date').daterangepicker({
      singleDatePicker: true,
      showDropdowns: true,
      locale: { format: 'YYYY-MM-DD' }
    });
  
    // Soumettre lot (ajout de stock)
    $('#lotForm').submit(function (e) {
      e.preventDefault();
  
      const data = {
        produit_id: $('#lot-produit-id').val(),
        quantite: parseInt($('#lot-quantite').val()),
        prix_unitaire_achat: parseFloat($('#lot-prix').val()),
        date_achat: $('#lot-date').val(),
        fournisseur: $('#lot-fournisseur').val(), 
      };
  
      $.ajax({
        url: '/api/produits/lots',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function () {
          $('#stockModal').modal('hide');
          tableProduits.ajax.reload();
          updateStats();
          showToastModal({ message: 'Lot ajouté avec succès', type: 'success' });
        },
        error: function () {
          showToastModal({ message: 'Erreur lors de l’ajout du lot', type: 'error' });
        }
      });
    });

    $('#btn-print-products').on('click', function () {
        const tableClone = $('#produitsTable').clone();
      
        // Retirer les colonnes inutiles
        tableClone.find('thead th:last-child').remove();
        tableClone.find('tbody tr').each(function () {
          $(this).find('td:last-child').remove();
        });
      
        const htmlContent = `
          <!doctype html>
          <html>
            <head>
              <meta charset="utf-8">
              <title>Liste des Produits</title>
              <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
              <style>
                body { padding: 20px; font-family: sans-serif; }
                table { width: 100%; border-collapse: collapse; font-size: 14px; }
                th, td { padding: 8px; border: 1px solid #ccc; }
                h3 { margin-bottom: 20px; }
              </style>
            </head>
            <body>
              <h3>Liste des Produits</h3>
              ${tableClone.prop('outerHTML')}
            </body>
          </html>
        `;
      
        const printWindow = window.open('', '_blank');
        printWindow.document.open();
        printWindow.document.write(htmlContent);
        printWindow.document.close();
      
        printWindow.onload = function () {
          printWindow.focus();
          printWindow.print();
          setTimeout(() => printWindow.close(), 500);
        };
      });
      
  
    // Initialisation
    initTable();
    updateStats();

    let selectedLotId = null;

$('#produitsTable').on('click', '.btn-lot', function () {
  const produitId = $(this).data('id');
  $('#lotsTable tbody').empty();

  $.get(`/api/produits/${produitId}/lots`, function (lots) {
    lots.forEach(lot => {
      $('#lotsTable tbody').append(`
        <tr>
          <td>${lot.id}</td>
          <td>${lot.quantite}</td>
          <td>${lot.prix_unitaire_achat} FCFA</td>
          <td>${lot.date_achat}</td>
          <td>${lot.fournisseur || '-'}</td>
          <td>${lot.devise || '-'}</td>
          <td>
            <button class="btn btn-sm btn-outline-danger btn-delete-lot" data-id="${lot.id}">
              <i class="bi bi-trash"></i>
            </button>
          </td>
        </tr>
      `);
    });
    $('#lotsModal').modal('show');
  });
});

// Clic bouton suppression de lot
$('#lotsTable').on('click', '.btn-delete-lot', function () {
  selectedLotId = $(this).data('id');
  $('#confirmDeleteLotModal').modal('show');
});

// Confirmer suppression
$('#btn-confirm-delete-lot').on('click', function () {
  if (!selectedLotId) return;

  $.ajax({
    url: `/api/produits/lots/${selectedLotId}`,
    method: 'DELETE',
    success: function () {
      $('#confirmDeleteLotModal').modal('hide');
      $(`#lotsTable button[data-id="${selectedLotId}"]`).closest('tr').remove();
      showToastModal({ message: 'Lot supprimé', type: 'success' });
      updateStats();
      tableProduits.ajax.reload();
    },
    error: function () {
      showToastModal({ message: 'Erreur de suppression du lot', type: 'error' });
    }
  });
});
   
      // Fermer modal lot
      $('#btn-close-lots').click(function () {
        $('#lotsModal').modal('hide');
      });
    
      // Fermer modal produit
      $('#btn-close-produit').click(function () {
        $('#produitModal').modal('hide');
      });
    
      // Fermer modal stock
      $('#btn-close-stock').click(function () {
        $('#stockModal').modal('hide');
      });
    
      // Fermer modal confirmation de suppression de lot
      $('#btn-close-confirm-delete-lot').click(function () {
        $('#confirmDeleteLotModal').modal('hide');
      });
  });
  