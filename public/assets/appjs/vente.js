$(document).ready(function () {
    let index = 1;
    let tableVentes = null;
    let produits = [];

    function chargerProduits() {
        $.get('/api/produits', function (res) {
            produits = res;
        }).fail(function () {
            showToastModal({ message: 'Erreur lors du chargement des produits', type: 'error' });
        });
    }

    chargerProduits();

    $(document).on('change', '.produit-id', function () {
        const select = $(this);
        const ligne = select.closest('.ligne-produit');
        const produitId = select.val();

        const produit = produits.find(p => p.id == produitId);
        if (produit) {
            ligne.data('stock-max', produit.stock_actuel);
            ligne.data('pme', produit.pme);

            ligne.find('.pme-affiche').text(`${produit.pme} FCFA`);
            ligne.find('.stock-restant').text(`${produit.stock_actuel} dispo`);
            ligne.find('.prix-unitaire').val(produit.pme);
            ligne.find('.prix-de-vente-ligne').text(`${produit.prix_de_vente} FCFA`);
        }

        calculerMontantLigne(ligne);
        calculerTotalGlobal();
    });

    $(document).on('input', '.quantite', function () {
        const ligne = $(this).closest('.ligne-produit');
        const maxStock = parseInt(ligne.data('stock-max')) || 0;
        const val = parseInt($(this).val());

        if (val > maxStock) {
            showToastModal({
                message: 'Quantité demandée > stock dispo !',
                type: 'warning'
            });
            $(this).val(maxStock);
        }
        calculerTotalGlobal();
    });

    function verifierLignesProduits() {
        const nbLignes = $('.ligne-produit').length;
        $('#alertAucuneLigne').toggle(nbLignes === 0);
    }

    $('#ajouterLigne').on('click', function () {
        const ligne = `
            <div class="ligne-produit row align-items-end mb-3" data-index="${index}" data-stock-max="0" data-pme="0">
                <div class="col-md-3">
                    <label class="form-label">Produit</label>
                    <select class="form-select produit-id" required>
                        <option value="">-- Choisir --</option>
                        ${produits.map(p => `<option value="${p.id}">${p.nom}</option>`).join('')}
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Qté</label>
                    <input type="number" class="form-control quantite" min="1" value="1" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">PU (FCFA)</label>
                    <input type="number" class="form-control prix-unitaire" step="0.01" min="0" required>
                </div>
                <div class="col-md-1">
                    <label class="form-label">PME</label>
                    <div class="form-control-plaintext pme-affiche text-muted">--</div>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Stock</label>
                    <div class="form-control-plaintext stock-restant text-muted">--</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Montant</label>
                    <div class="form-control-plaintext montant-ligne fw-bold">0 FCFA</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Prix de vente</label>
                    <div class="form-control-plaintext prix-de-vente-ligne text-info">0 FCFA</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Bénéfice</label>
                    <div class="form-control-plaintext benefice-ligne text-success">0 FCFA</div>
                </div>
                <div class="col-md-1 text-end">
                    <button type="button" class="btn btn-outline-danger btn-sm mt-4 remove-ligne">
                    <i class="bi bi-x-circle"></i>
                    </button>
                </div>
            </div>`;
        $('#ligneProduits').append(ligne);
        index++;
        verifierLignesProduits();
    });

    $(document).on('click', '.remove-ligne', function () {
        $(this).closest('.ligne-produit').remove();
        calculerTotalGlobal();
    });

    $(document).on('input', '.quantite, .prix-unitaire', function () {
        const ligne = $(this).closest('.ligne-produit');
        const maxStock = parseInt(ligne.data('stock-max')) || 0;
        const qte = parseInt(ligne.find('.quantite').val()) || 0;

        if (qte > maxStock) {
            showToastModal({ message: 'Quantité > stock dispo !', type: 'warning' });
            ligne.find('.quantite').val(maxStock);
        }

        calculerMontantLigne(ligne);
        calculerTotalGlobal();
    });

    function calculerMontantLigne(ligne) {
        const qte = parseFloat(ligne.find('.quantite').val()) || 0;
        const pu = parseFloat(ligne.find('.prix-unitaire').val()) || 0;
        const pme = parseFloat(ligne.data('pme')) || 0;

        const montant = qte * pu;
        const benefice = qte * (pu - pme);

        ligne.find('.montant-ligne').text(`${montant.toFixed(2)} FCFA`);
        ligne.find('.benefice-ligne').text(`${benefice.toFixed(2)} FCFA`);
    }

    function calculerTotalGlobal() {
        let total = 0;
        let totalBenefice = 0;

        $('.ligne-produit').each(function () {
            const ligne = $(this);
            const qte = parseFloat(ligne.find('.quantite').val()) || 0;
            const pu = parseFloat(ligne.find('.prix-unitaire').val()) || 0;
            const pme = parseFloat(ligne.data('pme')) || 0;

            total += qte * pu;
            totalBenefice += qte * (pu - pme);
        });

        const typePaiement = $('#type_paiement').val();
        if (typePaiement === 'especes') {
            $('#montant_paye').val(total.toFixed(2)).prop('disabled', true);
        } else {
            $('#montant_paye').prop('disabled', false);
        }

        const montantPaye = parseFloat($('#montant_paye').val()) || 0;
        const reste = Math.max(total - montantPaye, 0);

        $('#venteTotal').text(`${total.toFixed(2)} FCFA`);
        $('#venteReste').text(`${reste.toFixed(2)} FCFA`);
        $('#venteBenefice').text(`${totalBenefice.toFixed(2)} FCFA`);
        $('#venteResume').show();
    }

    $('#type_paiement').on('change', function () {
        calculerTotalGlobal(); 
    });

    $('#formVente').on('submit', function (e) {
        e.preventDefault();

        const lignes = [];
        let valid = true;

        $('.ligne-produit').each(function () {
            const produit_id = $(this).find('.produit-id').val();
            const quantite = parseFloat($(this).find('.quantite').val());
            const pu = parseFloat($(this).find('.prix-unitaire').val());

            if (!produit_id || quantite <= 0 || pu <= 0) {
                valid = false;
            }

            lignes.push({ produit_id, quantite, prix_unitaire: pu });
        });

        if (!valid || lignes.length === 0) {
            showToastModal({ message: 'Veuillez vérifier les champs des produits.', type: 'warning' });
            return;
        }

        const data = {
            client: $('#client').val(),
            type_paiement: $('#type_paiement').val(),
            montant_paye: parseFloat($('#montant_paye').val()) || 0,
            lignes: lignes
        };

        $.ajax({
            url: '/api/ventes/create',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            success: function (res) {
                showToastModal({ message: 'Vente enregistrée avec succès', type: 'success' });
                $('#formVente')[0].reset();
                $('#ligneProduits').html('');
                $('#ajouterLigne').trigger('click');
                $('#venteResume').hide();
                tableVentes.ajax.reload();
            },
            error: function () {
                showToastModal({ message: 'Erreur lors de l\'enregistrement', type: 'error' });
            }
        });
    });

    tableVentes = $('#ventesTable').DataTable({
        ajax: {
            url: '/api/ventes',
            data: function (d) {
                d.periode = $('#filtre-periode').val();
                d.client = $('#filtre-client').val();
            }
        },
        columns: [
            {
                className: 'details-control',
                orderable: false,
                data: null,
                defaultContent: '<i class="bi bi-caret-down-square-fill"></i>',
                width: "20px"
            },
            { data: 'id' },
            { data: 'date' },
            { data: 'client' },
            { data: 'total' },
            { data: 'montant_paye' },
            {
                data: null,
                render: function (data) {
                    return `<div class="d-flex justify-content-start gap-1"> 
                                <button class="btn btn-sm btn-outline-secondary btn-print" data-id="${data.id}">
                                    <i class="bi bi-receipt"></i>
                                </button>

                                <button class="btn btn-sm btn-outline-danger btn-annuler" data-id="${data.id}">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </div>
                    `;
                }
            }
        ],
        order: [[2, 'desc']],language: {
          url: '/api/DataTableFRJson'
        }
    });

    $('#ventesTable tbody').on('click', 'td.details-control', function () {
        const tr = $(this).closest('tr');
        const row = tableVentes.row(tr);

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown');
        } else {
            $.get('/api/ventes/' + row.data().id + '/details', function (res) {
                const html = formatDetailsTable(res.lignes);
                row.child(html).show();
                tr.addClass('shown');
            });
        }
    });

    function formatDetailsTable(lignes) {
        let html = `<table class="table table-sm table-bordered mt-2">
        <thead><tr><th>Produit</th><th>Qté</th><th>PU</th><th>Total</th></tr></thead><tbody>`;
        lignes.forEach(l => {
            const total = l.quantite * l.prix_unitaire;
            
        });
        html += '</tbody></table>';
        return html;
    }

    $('#btn-filtrer-ventes').on('click', function () {
        tableVentes.ajax.reload();
        chargerStatistiques();
    });

    $('#filtre-periode').daterangepicker({
        autoUpdateInput: false,
        locale: { cancelLabel: 'Annuler', applyLabel: 'Appliquer', format: 'YYYY-MM-DD' }
    });

    $('#filtre-periode').on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
    }).on('cancel.daterangepicker', function () {
        $(this).val('');
    });

    $('#btnPrintVentes').on('click', function () {
        const printContent = document.getElementById('ventesTable').outerHTML;
        const win = window.open('', '', 'width=1000,height=600');
        win.document.write(`
        <html>
        <head>
            <title>Impression des Ventes</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                }
                h1 {
                    text-align: center;
                    color: #333;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                }
                th {
                    background-color: #f2f2f2;
                    font-weight: bold;
                }
                tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                tr:hover {
                    background-color: #f1f1f1;
                }
                .text-right {
                    text-align: right;
                }
                .text-center {
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <h1>Rapport des Ventes</h1>
            ${printContent}
        </body>
    </html>`);
        win.document.close();
        win.print();
    });

    verifierLignesProduits();

    function chargerStatistiques() {
        const periode = $('#filtre-periode').val();
        const client = $('#filtre-client').val();

        $.get('/api/ventes/stats', { periode, client }, function (data) {
            $('#stat-nb-total').text(data.nb_total);
            $('#stat-total').text(data.montant_total.toLocaleString());

            $('#stat-nb-especes').text(data.nb_especes);
            $('#stat-especes').text(data.montant_especes.toLocaleString());

            $('#stat-nb-credit').text(data.nb_credit);
            $('#stat-credit').text(data.montant_credit.toLocaleString());

            $('#stat-recu').text(data.recette.toLocaleString());
            $('#stat-benef').text(data.benefice.toLocaleString());
        });
    }

    chargerStatistiques();

    $('#ventesTable').on('click', '.btn-print', function () {
        const id = $(this).data('id');

        $.get('/api/ventes/' + id + '/details', function (vente) {
        let lignesHTML = '';
        let total = 0;
        let benefice = 0;

        vente.lignes.forEach(l => {
        const sousTotal = l.quantite * l.prix_unitaire;
        const benef = (l.prix_unitaire - (l.pme || 0)) * l.quantite;
        total += sousTotal;
        benefice += benef;

        lignesHTML += `
            <tr>
            <td>${l.produit}</td>
            <td>${l.quantite}</td>
            <td>${l.prix_unitaire} FCFA</td>
            <td>${sousTotal.toFixed(2)} FCFA</td>
            </tr>`;
        });

        const html = `
        <div style="font-family: sans-serif; font-size: 13px; color: #000;">
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


            <div style="margin: 40px 0; display: flex; justify-content: space-between;">
            <div><strong>Client :</strong> ${vente.client || '---'}</div>
            <div><strong>Date :</strong> ${vente.date}</div>
         </div>

            <table class="table table-stripped table-bordered" border="1" cellspacing="0" cellpadding="5" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f0f0f0;">
                <th>Produit</th>
                <th>Qté</th>
                <th>PU</th>
                <th>Total</th>
                </tr>
            </thead>
            <tbody>
                ${lignesHTML}
            </tbody>
            </table>

            <div style="margin-top: 15px; text-align: right;">
            <p><strong>Total :</strong> ${total.toFixed(2)} FCFA</p>
            <p><strong>Payé :</strong> ${vente.montant_paye} FCFA</p>
            <p><strong>Reste :</strong> ${(total - vente.montant_paye).toFixed(2)} FCFA</p>
            <p><strong>Bénéfice estimé :</strong> ${benefice.toFixed(2)} FCFA</p>
            </div>

            <div style="margin-top: 30px; display: flex; justify-content: space-between;">
            <div><strong>Cachet / Signature</strong></div>
            <div><strong>Signature client</strong></div>
            </div>

            <hr style="position: absolute; left: 0; right: 0; bottom: 55px; margin: 0;">
            <p style="text-align: center; font-size: 12px; position: absolute; left: 0; right: 0; bottom: 30px; margin: 0;">
                Merci pour votre confiance - Auto Banamba
            </p>
        </div>
        `;

        const win = window.open('', '', 'width=900,height=700');
        win.document.write(`
        <html><head><title>Reçu Vente</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <style>body { padding: 20px; }</style>
        </head><body onload="window.print(); setTimeout(() => window.close(), 500);">${html}</body></html>
        `);
        win.document.close();
    });
    });

    $('#ventesTable').on('click', '.btn-annuler', function () {
  const id = $(this).data('id');
  if (!confirm("Annuler cette vente ? Cette action est irréversible.")) return;

  $.ajax({
    url: `/api/ventes/${id}/annuler`,
    method: 'DELETE',
    success: function () {
      showToastModal({ message: 'Vente annulée', type: 'success' });
      tableVentes.ajax.reload();
      chargerStatistiques();
    },
    error: function () {
      showToastModal({ message: 'Erreur lors de l’annulation', type: 'error' });
    }
  });
});

});
