/**
 * SmartPark - Main JavaScript
 * Custom functions and AJAX operations
 */

$(document).ready(function() {
    // Inicializa a aplicação
    initializeApp();
});

/**
 * Inicializa a aplicação
 */
function initializeApp() {
    console.log('SmartPark initialized');
}

/**
 * Filtra tabela
 */
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toUpperCase();
    const table = document.getElementById(tableId);
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        let found = false;
        const td = tr[i].getElementsByTagName('td');

        for (let j = 0; j < td.length; j++) {
            if (td[j]) {
                const txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }

        tr[i].style.display = found ? '' : 'none';
    }
}

/**
 * Confirmação de exclusão
 */
function confirmDelete(message = 'Tem certeza que deseja excluir?') {
    return confirm(message);
}

/**
 * Formata input de moeda
 */
function formatCurrencyInput(input) {
    let value = input.value.replace(/\D/g, '');
    value = (value / 100).toFixed(2);
    input.value = value;
}

/**
 * Calcula preço da reserva
 */
function calculateReservationPrice(startDateTime, endDateTime, pricePerHour) {
    const start = new Date(startDateTime);
    const end = new Date(endDateTime);

    if (end <= start) return 0;

    const diffMs = end - start;
    const diffHours = diffMs / (1000 * 60 * 60);

    return (diffHours * pricePerHour).toFixed(2);
}

/**
 * Atualiza exibição do preço
 */
function updatePriceDisplay(elementId, price) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = parseFloat(price).toFixed(2).replace('.', ',') + ' MT';
    }
}

/**
 * AJAX: verifica disponibilidade da vaga
 */
function checkVagaAvailability(vagaId, startDateTime, endDateTime, callback) {
    $.ajax({
        url: 'api/check-availability.php', // caminho relativo
        method: 'POST',
        data: {
            vaga_id: vagaId,
            data_inicio: startDateTime,
            data_fim: endDateTime
        },
        dataType: 'json',
        success: function(response) {
            if (callback) callback(response);
        },
        error: function() {
            console.error('Erro ao verificar disponibilidade');
        }
    });
}

/**
 * AJAX: atualiza status da vaga
 */
function updateVagaStatus(vagaId, newStatus, callback) {
    $.ajax({
        url: 'api/update-vaga-status.php', // caminho relativo
        method: 'POST',
        data: {
            vaga_id: vagaId,
            status: newStatus
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                if (callback) callback(response);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Erro ao atualizar status: ' + response.message,
                    confirmButtonColor: '#1E3A8A'
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Erro de Conexão',
                text: 'Erro ao atualizar status da vaga',
                confirmButtonColor: '#1E3A8A'
            });
        }
    });
}

/**
 * AJAX: detalhes do estacionamento
 */
function getEstacionamentoDetails(estacionamentoId, callback) {
    $.ajax({
        url: 'api/get-estacionamento.php', // caminho relativo
        method: 'GET',
        data: { id: estacionamentoId },
        dataType: 'json',
        success: function(response) {
            if (callback) callback(response);
        },
        error: function() {
            console.error('Erro ao buscar detalhes do estacionamento');
        }
    });
}

/**
 * Validação de formulário
 */
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('border-red-500');
            isValid = false;
        } else {
            input.classList.remove('border-red-500');
        }
    });

    return isValid;
}

/**
 * Validação de email
 */
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Mostra loading
 */
function showLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Carregando...';
    }
}

/**
 * Esconde loading
 */
function hideLoading(elementId, originalContent) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = originalContent;
    }
}

/**
 * Copia para área de transferência
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        Swal.fire({
            icon: 'success',
            title: 'Copiado!',
            text: 'Copiado para a área de transferência!',
            timer: 2000,
            showConfirmButton: false
        });
    }, function() {
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: 'Erro ao copiar',
            timer: 2000,
            showConfirmButton: false
        });
    });
}

/**
 * Imprime elemento
 */
function printElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        const printWindow = window.open('', '', 'height=600,width=800');
        printWindow.document.write('<html><head><title>Imprimir</title>');
        printWindow.document.write('<link href="https://cdn.tailwindcss.com" rel="stylesheet">');
        printWindow.document.write('</head><body>');
        printWindow.document.write(element.innerHTML);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.print();
    }
}

/**
 * Exporta tabela para CSV
 */
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;

    let csv = [];
    const rows = table.querySelectorAll('tr');

    for (let i = 0; i < rows.length; i++) {
        const row = [];
        const cols = rows[i].querySelectorAll('td, th');

        for (let j = 0; j < cols.length; j++) {
            row.push(cols[j].innerText);
        }

        csv.push(row.join(','));
    }

    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');

    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

/**
 * Registro do Service Worker (caminho relativo)
 */
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function () {
    navigator.serviceWorker
      .register('service-worker.js') // caminho relativo ao index.php
      .then(function () {
        console.log('Service Worker registrado com sucesso');
      })
      .catch(function (error) {
        console.log('Erro ao registrar Service Worker:', error);
      });
  });
}
