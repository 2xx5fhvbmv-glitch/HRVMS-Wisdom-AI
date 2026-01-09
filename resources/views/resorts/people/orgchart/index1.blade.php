@extends('resorts.layouts.app')
@section('page_tab_title' ,"Onboarding Creation")

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')  
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>People</span>
                            <h1>Organization Chart</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card pdngX-0 pb-0">
                <div class="card-header mb-0">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-4 col-md-5 col-sm-6 col-8">
                            <select class="form-select select2t-none" id="departmentSelect" data-placeholder="Select Department">
                                <option value="">Select Department</option>
                                @if($departments)
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-auto">
                            <a href="#" class="btn btn-themeBlue btn-sm" id="submitBtn">Submit</a>
                        </div>
                        <div class="col-auto ms-auto pe-0">
                            <a href="#" class="btn btn-themeSkyblue btn-sm" id="exportPdfBtn">Export</a>
                        </div>
                        <div class="col-auto">
                            <a href="#" class="btn btn-themeSkyblue btn-sm" id="exportPdfAlternativeBtn" title="HTML2PDF Export">HTML2PDF</a>
                        </div>
                        <div class="col-auto">
                            <a href="#" class="btn btn-themeBlue btn-sm" id="refreshBtn">Refresh</a>
                        </div>
                    </div>
                </div>
                <div class="orgChart-block">
                    <div id=tree></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
    <style>
        #tree {
            width: 100%;
            height: 100%;
            overflow: auto;
        }

        svg {
            /* padding: 10px; */
        }

        [data-n-id] rect {
            fill: #FFFFFF;
            stroke: #DEDEDE;
            stroke-width: 1px;
            rx: 20;
            ry: 20;
        }


        .boc-edit-form-close {
            display: none;
        }

        .boc-edit-form-header {
            height: 160px !important;
            background-color: #014653 !important;
            border-radius: 0px 0px 30px 30px !important;
        }

        .boc-edit-form-avatar {
            width: 160px !important;
            height: 160px !important;
            top: 70px !important;
            border: 0 !important;
            box-shadow: 0px 3px 6px #00000029 !important;
        }

        .boc-edit-form {
            background-color: #f5f8f8 !important;
            border-radius: 10px;
            padding: 0;
            border-radius: 20px 0 0 20px !important;
            box-shadow: none;
            overflow: hidden;
            box-shadow: none !important;
        }

        .boc-edit-form-title {
            font-size: 28px !important;
            font-weight: 600;
            color: #222222 !important;
            padding-top: 245px !important;
            font-family: Poppins !important;
        }

        .boc-img-button {
            background-color: #2eacb3 !important
        }

        .boc-input>label {
            text-transform: capitalize;
        }

        .boc-edit-form input,
        .boc-edit-form select,
        .boc-edit-form textarea {
            background-color: #fff !important;
            border: 1px solid #ccc !important;
            color: #222 !important;
            font-family: Poppins !important;
        }

        .boc-edit-form button {
            background-color: #2eacb3 !important;
            color: #fff !important;
            border-radius: 5px
        }

        .boc-edit-form-instruments {
            display: none;
        }

        .boc-edit-form-fields {
            margin-top: 130px;
            margin-bottom: 20px;
            padding: 0 10px;
        }

        .boc-input>input,
        .boc-input>select {
            padding-top: 23px !important;
            height: 60px !important;
            margin-bottom: 6px;

        }

        .boc-input>label,
        .boc-input>label {
            color: #666666 !important;
            font-family: Poppins !important;
        }

        .boc-input>label.focused,
        .boc-input>label.hasval {
            top: 5px !important;
        }

        .boc-dark ::-webkit-scrollbar,
        .boc-light ::-webkit-scrollbar {
            width: 5px !important;
            height: 5px !important;
        }

        .boc-light ::-webkit-scrollbar-corner {
            background: #01384238 !important;
        }

        .boc-light ::-webkit-scrollbar-thumb {
            background: #01384238 !important;
            opacity: 1 !important;
        }

        .boc-light ::-webkit-scrollbar-track {
            background: hsla(0, 0%, 95%, 0.4) !important;
            border: 0 !important
        }

        @media screen and (max-width: 1000px) {

            .boc-input>input,
            .boc-input>select {
                margin-bottom: 0;
            }

            .boc-edit-form {
                border-radius: 0 !important;
            }

            .boc-edit-form-fields {
                margin-bottom: 0;
                padding: 0 5px;
            }
        }
    </style>
@endsection

@section('import-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
// Enhanced OrgChart configuration with PDF export support
OrgChart.templates.polina = Object.assign({}, OrgChart.templates.ana);
OrgChart.templates.polina.link = '<path stroke-width="1px" stroke="#707070" fill="none" d="{edge}" />';
OrgChart.templates.polina.img_0 =
    '<clipPath id="{randId}"><circle cx="42" cy="42" r="32"></circle></clipPath>' +
    '<image preserveAspectRatio="xMidYMid slice" xlink:href="{val}" x="10" y="8" width="64" height="64" clip-path="url(#{randId})"></image>';
OrgChart.templates.polina.field_0 = '<text width="230" style="font-family: Poppins;font-size: 12px; font-weight:500; fill: #222;opacity:.6" x="82" y="22" text-anchor="start">{val}</text>';
OrgChart.templates.polina.field_1 = '<text width="130" text-overflow="multiline" style="font-family: Poppins;font-size: 16px; font-weight:600; fill: #222;" x="82" y="44" text-anchor="start">{val}</text>';
OrgChart.templates.polina.field_2 = '<text width="130" text-overflow="multiline" style="font-family: Poppins;font-size: 14px; font-weight:600;fill: #666666;" x="82" y="65" text-anchor="start">{val}</text>';
OrgChart.templates.polina.plus =
    '<circle cx="15" cy="15" r="10" fill="#fff" stroke="#DEDEDE" stroke-width="1"></circle>' +
    '<line x1="10" y1="15" x2="20" y2="15" stroke-width="1.5" stroke="rgb(34 34 34 / 60%)"></line>' +
    '<line x1="15" y1="10" x2="15" y2="20" stroke-width="1.5" stroke="rgb(34 34 34 / 60%)"></line>';
OrgChart.templates.polina.minus =
    '<circle cx="15" cy="15" r="10" fill="#fff" stroke="#DEDEDE" stroke-width="1"></circle>' +
    '<line x1="10" y1="15" x2="20" y2="15" stroke-width="1.5" stroke="rgb(34 34 34 / 60%)"></line>';
OrgChart.templates.polina.editForm = null;

// Global chart variable
let chart;

// Function to convert image to base64 (needed for PDF export)
function convertImageToBase64(url) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = function() {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = this.naturalWidth;
            canvas.height = this.naturalHeight;
            ctx.drawImage(this, 0, 0);
            try {
                const dataURL = canvas.toDataURL('image/png');
                resolve(dataURL);
            } catch (e) {
                resolve(url); // Fallback to original URL
            }
        };
        img.onerror = () => resolve(url); // Fallback to original URL
        img.src = url;
    });
}

// Function to prepare nodes for PDF export
async function prepareNodesForPDF(nodes) {
    const processedNodes = [];
    
    for (const node of nodes) {
        const processedNode = { ...node };
        
        // Convert image URL to base64 for PDF compatibility
        if (node.img && node.img !== '') {
            try {
                processedNode.img = await convertImageToBase64(node.img);
            } catch (error) {
                console.warn('Failed to convert image for node:', node.id, error);
                // Use fallback image
                processedNode.img = await convertImageToBase64('/admin_assets/files/user-image.png');
            }
        }
        
        processedNodes.push(processedNode);
    }
    
    return processedNodes;
}

// Fetch employee data
function fetchEmployeeData(departmentId = null) {
    let url = '{{ route("people.org-chart.getEmployees") }}';
    if (departmentId) {
        url += `?department_id=${departmentId}`;
    }

    return $.ajax({
        url: url,
        method: 'GET',
        dataType: 'json'
    });
}

// Initialize or update the OrgChart
function initializeOrUpdateChart(departmentId = null) {
    fetchEmployeeData(departmentId).done(function(nodes) {
        const rootNodes = nodes.filter(node => !node.pid || !nodes.some(n => n.id === node.pid));

        if (rootNodes.length > 1) {
            nodes.unshift({
                id: -1,
                name: "Organization",
                position: "All Employees",
                joinDate: "",
                img: "{{ Common::GetResortLogo(Auth::guard('resort-admin')->user()->resort_id)}}",
            });

            rootNodes.forEach(node => {
                node.pid = -1;
            });
        }

        if (!chart) {
            chart = new OrgChart(document.getElementById("tree"), {
                template: "polina",
                enableSearch: false,
                nodeBinding: {
                    field_1: "name",
                    field_2: "position",
                    field_0: "joinDate",
                    img_0: "img"
                },
                collapse: { level: 2 },
                nodes: nodes,
                // Enhanced PDF export configuration
                pdfExport: {
                    format: "A4",
                    orientation: OrgChart.orientation.landscape, // Better for org charts
                    padding: 20,
                    template: "polina"
                }
            });

            chart.on('init', function() {
                chart.zoom(1);
            });
        } else {
            chart.load(nodes);
        }
    }).fail(function(error) {
        console.error('Error fetching employee data:', error);
    });
}

// Simplified and working PDF export function
function exportToPDF() {
    if (!chart) {
        alert('Chart not initialized');
        return;
    }

    try {
        // Show loading indicator
        const loadingOverlay = $('<div id="pdf-loading" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;"><div style="background: white; padding: 20px; border-radius: 5px;">Preparing PDF...</div></div>');
        $('body').append(loadingOverlay);

        // Expand all nodes first
        // chart.expandAll();

        // Wait a moment for expansion, then export
        setTimeout(() => {
            try {
                // Simple export with basic options
                chart.exportPDF({
                    format: "A4",
                    orientation: OrgChart.orientation.landscape,
                    padding: 20,
                    filename: `organization-chart-${new Date().toISOString().split('T')[0]}.pdf`
                });
                
                // Remove loading overlay after a delay
                setTimeout(() => {
                    $('#pdf-loading').remove();
                }, 2000);
                
            } catch (exportError) {
                console.error('PDF export error:', exportError);
                $('#pdf-loading').remove();
                
                // Fallback to html2pdf method
                exportToPDFAlternative();
            }
        }, 1000);

    } catch (error) {
        console.error('PDF export failed:', error);
        $('#pdf-loading').remove();
        alert('Failed to export PDF. Trying alternative method...');
        
        // Try alternative method
        exportToPDFAlternative();
    }
}

// Better alternative PDF export using html2pdf
function exportToPDFAlternative() {
    // Check if html2pdf is available
    if (typeof html2pdf === 'undefined') {
        alert('html2pdf library not loaded. Please include the library.');
        return;
    }

    // Show loading indicator
    const loadingOverlay = $('<div id="pdf-loading-alt" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;"><div style="background: white; padding: 20px; border-radius: 5px;">Generating PDF...</div></div>');
    $('body').append(loadingOverlay);

    // First, expand all nodes
    // chart.expandAll();
    
    setTimeout(() => {
        const element = document.getElementById('tree');
        
        // Create a wrapper div with better styling for PDF
        const wrapper = document.createElement('div');
        wrapper.innerHTML = `
            <div style="padding: 20px; font-family: Arial, sans-serif;">
                <h1 style="text-align: center; margin-bottom: 30px; color: #333;">Organization Chart</h1>
                <div style="width: 100%; overflow: hidden;">
                    ${element.outerHTML}
                </div>
                <div style="margin-top: 20px; text-align: center; font-size: 12px; color: #666;">
                    Generated on ${new Date().toLocaleDateString()} at ${new Date().toLocaleTimeString()}
                </div>
            </div>
        `;

        const opt = {
            margin: [10, 10, 10, 10],
            filename: `organization-chart-${new Date().toISOString().split('T')[0]}.pdf`,
            image: { 
                type: 'jpeg', 
                quality: 0.95 
            },
            html2canvas: { 
                scale: 1.5,
                useCORS: true,
                allowTaint: true,
                scrollX: 0,
                scrollY: 0,
                width: element.scrollWidth,
                height: element.scrollHeight,
                backgroundColor: '#ffffff'
            },
            jsPDF: { 
                unit: 'mm', 
                format: 'a4', 
                orientation: 'landscape',
                compress: true
            },
            pagebreak: { 
                mode: ['avoid-all', 'css', 'legacy'] 
            }
        };

        // Generate PDF
        html2pdf()
            .set(opt)
            .from(wrapper)
            .save()
            .then(() => {
                $('#pdf-loading-alt').remove();
                console.log('PDF generated successfully');
            })
            .catch((error) => {
                console.error('PDF generation failed:', error);
                $('#pdf-loading-alt').remove();
                alert('PDF generation failed. Please try again.');
            });
    }, 1000);
}

// DOM Ready
$(document).ready(function() {
    // Load initial chart
    initializeOrUpdateChart();

    // Filter department
    $('#submitBtn').click(function() {
        const departmentId = $('#departmentSelect').val();
        initializeOrUpdateChart(departmentId || null);
    });

    // Refresh chart
    $('#refreshBtn').click(function() {
        $('#departmentSelect').val('').trigger('change');
        initializeOrUpdateChart(null);
    });

    // Enhanced PDF export
    $('#exportPdfBtn').click(function(e) {
        e.preventDefault();
        exportToPDF();
    });

    // Alternative: Add second export button for html2pdf method
    if ($('#exportPdfAlternativeBtn').length) {
        $('#exportPdfAlternativeBtn').click(function(e) {
            e.preventDefault();
            exportToPDFAlternative();
        });
    }
});
</script>
@endsection
