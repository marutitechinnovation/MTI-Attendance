// ── Sidebar toggle ──────────────────────────────────────────
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');

document.getElementById('sidebarToggle')?.addEventListener('click', function () {
    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('show');
        overlay?.classList.toggle('show');
    } else {
        sidebar.classList.toggle('collapsed');
    }
});

overlay?.addEventListener('click', function () {
    sidebar.classList.remove('show');
    overlay.classList.remove('show');
});

// ── DataTables (Bootstrap 5 skin) ───────────────────────────
function initDataTables() {
    $('.datatable').each(function () {
        if ($.fn.DataTable.isDataTable(this)) {
            $(this).DataTable().destroy();
        }

        const tableId = $(this).attr('id');

        // Column-specific settings per table
        let columnDefs = [{ orderable: false, targets: -1 }];
        let paging = true;

        // Attendance table: also disable ordering on Break column (index 3 — contains HTML)
        if (tableId === 'attendance-table') {
            columnDefs = [
                { orderable: false, targets: [3, 8] }   // Break col + Actions col
            ];
        }

        // Employee detail table: no actions column, show all rows, custom export title
        let exportTitle = null;
        if (tableId === 'employee-detail-table') {
            columnDefs = [
                { orderable: false, targets: [2] }   // Break col only
            ];
            paging = false;
            exportTitle = $(this).data('export-title') || 'Attendance Detail';
        }

        $(this).DataTable({
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            paging: paging,

            //  Toolbar:  [Search LEFT]  ·············  [Buttons RIGHT]
            //  Table:    [t]
            //  Footer:   [Show · Info]  ·············  [Pagination]
            dom:
                "<'dt-toolbar d-flex align-items-center justify-content-between gap-3 px-3 py-3'fB>" +
                "<'table-responsive'tr>" +
                "<'dt-footer d-flex align-items-center justify-content-between flex-wrap gap-2 px-3 py-2'<'d-flex align-items-center gap-3'li>p>",

            buttons: [
                {
                    extend:    'excelHtml5',
                    text:      '<i class="bi bi-file-earmark-excel me-1"></i>Excel',
                    titleAttr: 'Export Excel',
                    className: 'dt-export-btn',
                    title:     exportTitle,
                    exportOptions: { columns: ':visible' }
                },
                {
                    text:      '<i class="bi bi-filetype-pdf me-1"></i>PDF',
                    titleAttr: 'Export PDF',
                    className: 'dt-export-btn dt-export-pdf',
                    action: function (e, dt, node, config) {
                        const { jsPDF } = window.jspdf;
                        const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

                        const title = exportTitle || document.querySelector('.topbar .fw-semibold')?.textContent || 'Report';
                        const now = new Date().toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });

                        // Helper: clean text (remove icon font chars that show as !)
                        const cleanText = (el) => {
                            let text = '';
                            $(el).contents().each(function() {
                                if (this.nodeType === 3) { // text node only
                                    text += this.textContent;
                                } else if ($(this).is('span, a, b, strong, em, small')) {
                                    text += $(this).text();
                                }
                            });
                            return text.trim().replace(/\s+/g, ' ') || $(el).text().trim().replace(/\s+/g, ' ');
                        };

                        // Header bar
                        doc.setFillColor(13, 110, 253);
                        doc.rect(0, 0, 297, 18, 'F');
                        doc.setTextColor(255, 255, 255);
                        doc.setFontSize(14);
                        doc.setFont(undefined, 'bold');
                        doc.text(title, 14, 11);
                        doc.setFontSize(9);
                        doc.setFont(undefined, 'normal');
                        doc.text('Generated: ' + now, 283, 11, { align: 'right' });

                        // Extract headers (strip icon text)
                        const headers = [];
                        dt.columns(':visible').every(function () {
                            const $th = $(this.header());
                            // Get text without icon <i> content
                            const clone = $th.clone();
                            clone.find('i').remove();
                            headers.push(clone.text().trim());
                        });

                        // Extract body rows
                        const rows = [];
                        dt.rows({ search: 'applied' }).every(function () {
                            const row = [];
                            const cells = $(this.node()).find('td:visible');
                            cells.each(function () {
                                const clone = $(this).clone();
                                clone.find('i').remove();
                                let cellText = clone.text().trim().replace(/\s+/g, ' ');
                                // Replace Unicode arrows that jsPDF can't render
                                cellText = cellText.replace(/[\u2192\u2190\u2194\u21D2\u279C]/g, '->');
                                row.push(cellText);
                            });
                            rows.push(row);
                        });

                        // Extract tfoot (totals row)
                        const footRows = [];
                        const $tfoot = $(dt.table().node()).find('tfoot tr');
                        if ($tfoot.length) {
                            $tfoot.each(function () {
                                const footRow = [];
                                $(this).find('td, th').each(function () {
                                    const clone = $(this).clone();
                                    clone.find('i').remove();
                                    const colspan = parseInt($(this).attr('colspan')) || 1;
                                    const cellText = clone.text().trim().replace(/\s+/g, ' ');
                                    footRow.push({ content: cellText, colSpan: colspan, styles: { fontStyle: 'bold', fillColor: [33, 37, 41], textColor: [255, 255, 255] } });
                                });
                                footRows.push(footRow);
                            });
                        }

                        // AutoTable
                        const tableConfig = {
                            head: [headers],
                            body: rows,
                            startY: 22,
                            showFoot: 'lastPage',
                            theme: 'grid',
                            styles: {
                                fontSize: 8,
                                cellPadding: 2.5,
                                lineColor: [220, 220, 220],
                                lineWidth: 0.3,
                                overflow: 'linebreak',
                            },
                            headStyles: {
                                fillColor: [13, 110, 253],
                                textColor: [255, 255, 255],
                                fontStyle: 'bold',
                                fontSize: 8.5,
                                halign: 'center',
                            },
                            alternateRowStyles: {
                                fillColor: [245, 247, 250],
                            },
                            columnStyles: {
                                0: { cellWidth: 28 },
                            },
                            didParseCell: function(data) {
                                if (data.section === 'body') {
                                    const text = data.cell.raw || '';
                                    if (text.includes('Present'))  data.cell.styles.textColor = [25, 135, 84];
                                    if (text.includes('Absent'))   data.cell.styles.textColor = [220, 53, 69];
                                    if (text.includes('Late') || text.includes('Flagged')) data.cell.styles.textColor = [255, 193, 7];
                                    if (text.includes('Weekend') || text.includes('Holiday')) data.cell.styles.textColor = [108, 117, 125];
                                }
                            },
                            margin: { top: 22, left: 10, right: 10 },
                        };

                        if (footRows.length) {
                            tableConfig.foot = footRows;
                            tableConfig.footStyles = {
                                fillColor: [33, 37, 41],
                                textColor: [255, 255, 255],
                                fontStyle: 'bold',
                                fontSize: 8.5,
                            };
                        }

                        doc.autoTable(tableConfig);

                        // Page footer
                        const pageCount = doc.internal.getNumberOfPages();
                        for (let i = 1; i <= pageCount; i++) {
                            doc.setPage(i);
                            doc.setFontSize(7);
                            doc.setTextColor(150);
                            doc.text('Page ' + i + ' of ' + pageCount, 283, 200, { align: 'right' });
                            doc.text('MTI Attendance System', 14, 200);
                        }

                        const filename = (exportTitle || 'report').replace(/[^a-zA-Z0-9]/g, '_') + '.pdf';
                        doc.save(filename);
                    }
                },
                {
                    extend:    'print',
                    text:      '<i class="bi bi-printer me-1"></i>Print',
                    titleAttr: 'Print',
                    className: 'dt-export-btn',
                    title:     exportTitle,
                    exportOptions: { columns: ':visible' }
                }
            ],

            language: {
                search:            '',
                searchPlaceholder: 'Search…',
                lengthMenu:        'Show _MENU_',
                info:              'Showing <strong>_START_–_END_</strong> of <strong>_TOTAL_</strong>',
                infoEmpty:         'No records',
                emptyTable:        'No data available',
                paginate: {
                    next:     '<i class="bi bi-chevron-right"></i>',
                    previous: '<i class="bi bi-chevron-left"></i>'
                }
            },

            columnDefs: columnDefs
        });
    });
}

// Intercept GET forms (like filters) to use AJAX instead of URL-based page reloads
$(document).on('submit', 'form[method="GET"]:not(.no-ajax)', function(e) {
    if ($(this).find('input[type="date"], input[type="month"], select, input[type="text"]').length > 0) {
        e.preventDefault();
        const form = $(this);
        const url = form.attr('action') || window.location.pathname;
        const formData = form.serialize();
        const fullUrl = url + '?' + formData;
        
        const btn = form.find('button[type="submit"]');
        const origContent = btn.html();
        if (btn.length > 0) {
            btn.html('<span class="spinner-border spinner-border-sm me-1" style="width:14px;height:14px;"></span> Filtering...');
            btn.prop('disabled', true);
        }

        $.ajax({
            url: fullUrl,
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(response) {
                // Parse full HTML string into a DOM
                const parser = new DOMParser();
                const doc = parser.parseFromString(response, 'text/html');
                const newContent = doc.querySelector('main.content');

                if (newContent) {
                    // Update the page content smoothly
                    $('main.content').html(newContent.innerHTML);
                    // Reinitialize custom elements like Datatables
                    initDataTables();
                } else {
                    window.location.href = fullUrl; // Fallback
                }
            },
            error: function() {
                window.location.href = fullUrl; // Fallback
            },
            complete: function() {
                // If DOM wasn't replaced (like on error) restore the button
                if (document.body.contains(btn[0])) {
                    btn.html(origContent);
                    btn.prop('disabled', false);
                }
            }
        });
    }
});

$(document).ready(function () {
    initDataTables();
});

// ── Global Attendance Details Modal Handler (Handles AJAX-replaced content) ──
$(document).on('show.bs.modal', '#viewLogModal', function (event) {
    const button = event.relatedTarget;
    
    $('#modal-emp-name').text($(button).data('name'));
    $('#modal-emp-date').text($(button).data('date'));
    $('#modal-emp-in').text($(button).data('in'));
    $('#modal-emp-out').text($(button).data('out'));
    $('#modal-emp-breaks').html($(button).data('breaks') || '—');
    $('#modal-emp-gross').text($(button).data('gross') || '—');
    $('#modal-emp-breakhours').text($(button).data('breakhours') || '—');
    $('#modal-emp-net').text($(button).data('net'));

    // Handle Late row
    if ($(button).data('late') === 'Yes') {
        $('#modal-late-row').removeClass('d-none');
    } else {
        $('#modal-late-row').addClass('d-none');
    }

    // Handle Flag details & Map
    const flaggedData = $(button).data('flagged-scans');
    if (flaggedData && flaggedData.length > 0) {
        $('#modal-flag-row').removeClass('d-none');
        $('#modal-emp-flagdetails').html($(button).data('flagdetails'));

        // Initialize/reset map
        if (window.flagMap) {
            window.flagMap.remove();
        }

        setTimeout(() => {
            if (!document.getElementById('flag-map')) return;

            window.flagMap = L.map('flag-map');
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(window.flagMap);

            const markers = [];
            flaggedData.forEach(scan => {
                const scanPos = [scan.scan_lat, scan.scan_lng];
                const qrPos   = [scan.qr_lat, scan.qr_lng];

                // User Scan Location (Red)
                L.circleMarker(scanPos, {
                    radius: 6,
                    fillColor: "#dc3545",
                    color: "#fff",
                    weight: 2,
                    fillOpacity: 1
                }).addTo(window.flagMap).bindPopup("User Location");

                // QR Office Location (Blue)
                L.circleMarker(qrPos, {
                    radius: 7,
                    fillColor: "#0d6efd",
                    color: "#fff",
                    weight: 2,
                    fillOpacity: 1
                }).addTo(window.flagMap).bindPopup("Office: " + scan.location);

                // Line between them
                L.polyline([scanPos, qrPos], {
                    color: '#dc3545',
                    weight: 2,
                    dashArray: '5, 8',
                    opacity: 0.6
                }).addTo(window.flagMap);

                markers.push(scanPos, qrPos);
            });

            if (markers.length > 0) {
                window.flagMap.fitBounds(markers, { padding: [30, 30] });
            }
        }, 400); // Wait for modal animation
    } else {
        $('#modal-flag-row').addClass('d-none');
        if (window.flagMap) {
            window.flagMap.remove();
            window.flagMap = null;
        }
    }
});

