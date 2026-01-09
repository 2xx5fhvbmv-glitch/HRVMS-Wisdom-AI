@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

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
                            <h1>{{ $page_title }}</h1>
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
                        <!-- <div class="col-auto ms-auto pe-0">
                            <a href="#" class="btn btn-themeSkyblue btn-sm" id="exportPdfBtn">Export</a>
                        </div> -->
                        <!-- <div class="col-auto ms-auto pe-0">
                            <a href="#" class="btn btn-themeSkyblue btn-sm" id="exportPdfAlternativeBtn" title="HTML2PDF Export">HTML2PDF</a>
                        </div> -->
                         <div class="col-auto  ms-auto pe-0">
                            <a href="#" class="btn btn-themeSkyblue btn-sm" id="exportPdfCanvasBtn" title="Canvas Export (Most Reliable)">Export To PDF</a>
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
            rx: 10;
            ry: 10;
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
// OrgChart.templates.polina = Object.assign({}, OrgChart.templates.ana);
// OrgChart.templates.polina.link = '<path stroke-width="1px" stroke="#707070" fill="none" d="{edge}" />';
// OrgChart.templates.polina.img_0 =
//     '<clipPath id="{randId}"><circle cx="42" cy="42" r="32"></circle></clipPath>' +
//     '<image preserveAspectRatio="xMidYMid slice" xlink:href="{val}" x="10" y="8" width="64" height="64" clip-path="url(#{randId})" crossorigin="anonymous"></image>';
// OrgChart.templates.polina.field_0 = '<text width="400" style="font-family: Poppins;font-size: 12px; font-weight:500; fill: #222;opacity:.6" x="82" y="22" text-anchor="start">{val}</text>';
// OrgChart.templates.polina.field_1 = '<text width="200" text-overflow="multiline" style="font-family: Poppins;font-size: 16px; font-weight:600; fill: #222;" x="82" y="44" text-anchor="start">{val}</text>';
// OrgChart.templates.polina.field_2 = '<text width="200" text-overflow="multiline" style="font-family: Poppins;font-size: 14px; font-weight:600;fill: #666666;" x="82" y="65" text-anchor="start">{val}</text>';
// OrgChart.templates.polina.plus =
//     '<circle cx="15" cy="15" r="10" fill="#fff" stroke="#DEDEDE" stroke-width="1"></circle>' +
//     '<line x1="10" y1="15" x2="20" y2="15" stroke-width="1.5" stroke="rgb(34 34 34 / 60%)"></line>' +
//     '<line x1="15" y1="10" x2="15" y2="20" stroke-width="1.5" stroke="rgb(34 34 34 / 60%)"></line>';
// OrgChart.templates.polina.minus =
//     '<circle cx="15" cy="15" r="10" fill="#fff" stroke="#DEDEDE" stroke-width="1"></circle>' +
//     '<line x1="10" y1="15" x2="20" y2="15" stroke-width="1.5" stroke="rgb(34 34 34 / 60%)"></line>';
OrgChart.templates.polina.link = '<path stroke-width="1px" stroke="#DDDDDD" fill="none" d="{edge}" />';

        OrgChart.templates.polina.img_0 =
            '<clipPath id="{randId}"><circle cx="42" cy="42" r="32"></circle></clipPath>' +
            '<image preserveAspectRatio="xMidYMid slice" xlink:href="{val}" x="10" y="8" width="64" height="64" clip-path="url(#{randId})" stroke-width="1px"></image>';

    OrgChart.templates.polina.field_0 = '<text width="230" style="font-family: Poppins;font-size: 10px; font-weight:500; fill: #222;opacity:.6" x="82" y="22" text-anchor="start" class="field_0">{val}</text>';
    OrgChart.templates.polina.field_1 = '<text width="130" text-overflow="multiline" style="font-family: Poppins;font-size: 14px; font-weight:600; fill: #222;" x="82" y="44" text-anchor="start" class="field_1">{val}</text>';
    OrgChart.templates.polina.field_2 = '<text width="130" text-overflow="multiline" style="font-family: Poppins;font-size: 12px; font-weight:500;fill: #666666;" x="82" y="65" text-anchor="start" class="field_2">{val}</text>';

    OrgChart.templates.polina.plus =
        '<circle cx="15" cy="15" r="10" fill="#fff" stroke="#DEDEDE" stroke-width="1"></circle>' +
        '<line x1="10" y1="15" x2="20" y2="15" stroke-width="1.5" stroke="rgb(34 34 34 / 60%)"></line>' +
        '<line x1="15" y1="10" x2="15" y2="20" stroke-width="1.5" stroke="rgb(34 34 34 / 60%)"></line>';

    OrgChart.templates.polina.minus =
        '<circle cx="15" cy="15" r="10" fill="#fff" stroke="#DEDEDE" stroke-width="1"></circle>' +
        '<line x1="10" y1="15" x2="20" y2="15" stroke-width="1.5" stroke="rgb(34 34 34 / 60%)"></line>';
    OrgChart.templates.polina.link =
        '<path stroke-width="1px" stroke="#707070" fill="none" d="{edge}" />';
OrgChart.templates.polina.editForm = null;

// Global chart variable
let chart;

// Image preloading and conversion functions
async function preloadImages() {
    const images = document.querySelectorAll('#tree img, #tree image');
    console.log('Found images to preload:', images.length);
    
    const imagePromises = Array.from(images).map((img, index) => {
        return new Promise((resolve, reject) => {
            // For SVG images, we need to handle them differently
            if (img.tagName.toLowerCase() === 'image') {
                // SVG image element
                const src = img.getAttribute('xlink:href') || img.getAttribute('href');
                if (src && src.startsWith('http')) {
                    // Create a regular img element to test loading
                    const testImg = new Image();
                    testImg.crossOrigin = 'anonymous';
                    testImg.onload = () => {
                        console.log(`SVG Image ${index} loaded successfully:`, src);
                        resolve();
                    };
                    testImg.onerror = (error) => {
                        console.warn(`SVG Image ${index} failed to load:`, src, error);
                        resolve(); // Resolve anyway to not block the process
                    };
                    testImg.src = src;
                } else {
                    resolve();
                }
            } else {
                // Regular img element
                if (img.complete && img.naturalWidth > 0) {
                    console.log(`Image ${index} already loaded:`, img.src);
                    resolve();
                } else {
                    img.onload = () => {
                        console.log(`Image ${index} loaded:`, img.src);
                        resolve();
                    };
                    img.onerror = (error) => {
                        console.warn(`Image ${index} failed to load:`, img.src, error);
                        resolve(); // Resolve anyway to not block the process
                    };
                    
                    // If src is already set, it might trigger loading
                    if (!img.src) {
                        resolve();
                    }
                }
            }
        });
    });
    
    try {
        await Promise.all(imagePromises);
        console.log('All images preloaded successfully');
    } catch (error) {
        console.warn('Some images failed to preload:', error);
    }
}

// Convert images to base64 for better PDF compatibility
async function convertImagesToBase64() {
    const images = document.querySelectorAll('#tree img');
    const svgImages = document.querySelectorAll('#tree image');
    
    // Handle regular img elements
    for (let img of images) {
        try {
            if (img.src && !img.src.startsWith('data:')) {
                const base64 = await imageToBase64(img.src);
                if (base64) {
                    img.src = base64;
                }
            }
        } catch (error) {
            console.warn('Failed to convert image to base64:', img.src, error);
        }
    }
    
    // Handle SVG image elements
    for (let svgImg of svgImages) {
        try {
            const src = svgImg.getAttribute('xlink:href') || svgImg.getAttribute('href');
            if (src && !src.startsWith('data:') && src.startsWith('http')) {
                const base64 = await imageToBase64(src);
                if (base64) {
                    svgImg.setAttribute('xlink:href', base64);
                    svgImg.setAttribute('href', base64);
                }
            }
        } catch (error) {
            console.warn('Failed to convert SVG image to base64:', src, error);
        }
    }
}

// Helper function to convert image URL to base64
function imageToBase64(url) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        
        img.onload = function() {
            try {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                canvas.width = this.naturalWidth;
                canvas.height = this.naturalHeight;
                ctx.drawImage(this, 0, 0);
                const base64 = canvas.toDataURL('image/png');
                resolve(base64);
            } catch (error) {
                console.warn('Canvas conversion failed for:', url, error);
                resolve(null);
            }
        };
        
        img.onerror = function(error) {
            console.warn('Image loading failed for:', url, error);
            resolve(null);
        };
        
        img.src = url;
    });
}

// Enhanced canvas-based PDF export with image handling
async function exportToPDFCanvas() {
    // Show loading indicator
    const loadingOverlay = $('<div id="pdf-loading-canvas" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;"><div style="background: white; padding: 20px; border-radius: 5px; text-align: center;"><div>Creating PDF...</div><div style="margin-top: 10px; font-size: 12px;">Loading images...</div></div></div>');
    $('body').append(loadingOverlay);

    try {
        // Step 1: Preload all images
        console.log('Step 1: Preloading images...');
        await preloadImages();
        
        // Update loading message
        $('#pdf-loading-canvas .bg-white div:last-child').text('Converting images...');
        
        // Step 2: Convert images to base64
        console.log('Step 2: Converting images to base64...');
        await convertImagesToBase64();
        
        // Update loading message
        $('#pdf-loading-canvas .bg-white div:last-child').text('Generating PDF...');
        
        // Step 3: Wait a bit more for everything to settle
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        const element = document.getElementById('tree');
        
        console.log('Step 3: Capturing with html2canvas...');
        
        // Use html2canvas to capture the chart with enhanced image handling
        const canvas = await html2canvas(element, {
            scale: 2,
            useCORS: true,
            allowTaint: true,
            backgroundColor: '#ffffff',
            width: element.scrollWidth,
            height: element.scrollHeight,
            logging: false,
            imageTimeout: 15000,
            onclone: function(clonedDoc) {
                // Ensure all images in cloned document are properly set
                const clonedImages = clonedDoc.querySelectorAll('img, image');
                clonedImages.forEach(img => {
                    if (img.tagName.toLowerCase() === 'img') {
                        img.style.display = 'block';
                        img.style.maxWidth = 'none';
                        img.style.maxHeight = 'none';
                    }
                });
                
                // Ensure SVG elements are properly rendered
                const svgElements = clonedDoc.querySelectorAll('svg');
                svgElements.forEach(svg => {
                    svg.style.overflow = 'visible';
                });
            }
        });
        
        console.log('Step 4: Creating PDF...');
        
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('l', 'mm', 'a4'); // landscape, millimeters, A4
        
        const imgData = canvas.toDataURL('image/png', 1.0);
        const imgWidth = 297; // A4 landscape width in mm
        const pageHeight = 210; // A4 landscape height in mm
        const imgHeight = (canvas.height * imgWidth) / canvas.width;
        let heightLeft = imgHeight;
        
        let position = 0;
        
        // Add title
        pdf.setFontSize(16);
        pdf.text('Organization Chart', 148.5, 15, { align: 'center' });
        
        // Add the image
        pdf.addImage(imgData, 'PNG', 0, 25, imgWidth, imgHeight - 25);
        heightLeft -= pageHeight;
        
        // Add more pages if needed
        while (heightLeft >= 0) {
            position = heightLeft - imgHeight;
            pdf.addPage();
            pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
        }
        
        // Add footer
        const pageCount = pdf.internal.getNumberOfPages();
        for (let i = 1; i <= pageCount; i++) {
            pdf.setPage(i);
            pdf.setFontSize(10);
            pdf.text(`Generated on ${new Date().toLocaleDateString()} - Page ${i} of ${pageCount}`, 
                     148.5, 205, { align: 'center' });
        }
        
        // Save the PDF
        pdf.save(`organization-chart-${new Date().toISOString().split('T')[0]}.pdf`);
        
        console.log('PDF generated successfully!');
        
    } catch (error) {
        console.error('Canvas conversion failed:', error);
        alert('PDF generation failed: ' + error.message);
    } finally {
        $('#pdf-loading-canvas').remove();
    }
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
                    orientation: OrgChart.orientation.landscape,
                    padding: 20,
                    template: "polina"
                }
            });

            chart.on('init', function() {
                chart.zoom(1);
                // Preload images after chart initialization
                setTimeout(() => {
                    preloadImages();
                }, 1000);
            });
        } else {
            chart.load(nodes);
            // Preload images after chart update
            setTimeout(() => {
                preloadImages();
            }, 1000);
        }
    }).fail(function(error) {
        console.error('Error fetching employee data:', error);
    });
}

// Simplified and working PDF export function
// function exportToPDF() {
//     if (!chart) {
//         alert('Chart not initialized');
//         return;
//     }

//     try {
//         // Show loading indicator
//         const loadingOverlay = $('<div id="pdf-loading" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;"><div style="background: white; padding: 20px; border-radius: 5px;">Preparing PDF...</div></div>');
//         $('body').append(loadingOverlay);

//         setTimeout(() => {
//             try {
//                 // Simple export with basic options
//                 chart.exportPDF({
//                     format: "A4",
//                     orientation: OrgChart.orientation.landscape,
//                     padding: 20,
//                     filename: `organization-chart-${new Date().toISOString().split('T')[0]}.pdf`
//                 });
                
//                 setTimeout(() => {
//                     $('#pdf-loading').remove();
//                 }, 2000);
                
//             } catch (exportError) {
//                 console.error('PDF export error:', exportError);
//                 $('#pdf-loading').remove();
//                 exportToPDFAlternative();
//             }
//         }, 1000);

//     } catch (error) {
//         console.error('PDF export failed:', error);
//         $('#pdf-loading').remove();
//         alert('Failed to export PDF. Trying alternative method...');
//         exportToPDFAlternative();
//     }
// }

// Enhanced alternative PDF export with image handling
// async function exportToPDFAlternative() {
//     if (typeof html2pdf === 'undefined') {
//         alert('html2pdf library not loaded. Please include the library.');
//         return;
//     }

//     // Show loading indicator
//     const loadingOverlay = $('<div id="pdf-loading-alt" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;"><div style="background: white; padding: 20px; border-radius: 5px; text-align: center;"><div>Generating PDF...</div><div style="margin-top: 10px; font-size: 12px;">Processing images...</div></div></div>');
//     $('body').append(loadingOverlay);

//     try {
//         // Preload and convert images
//         await preloadImages();
//         $('#pdf-loading-alt .bg-white div:last-child').text('Converting images...');
//         await convertImagesToBase64();
//         $('#pdf-loading-alt .bg-white div:last-child').text('Creating PDF...');
        
//         // Wait for everything to settle
//         await new Promise(resolve => setTimeout(resolve, 1000));
        
//         const element = document.getElementById('tree');
        
//         // Create a wrapper div with better styling for PDF
//         const wrapper = document.createElement('div');
//         wrapper.innerHTML = `
//             <div style="padding: 20px; font-family: Arial, sans-serif;">
//                 <h1 style="text-align: center; margin-bottom: 30px; color: #333;">Organization Chart</h1>
//                 <div style="width: 100%; overflow: hidden;">
//                     ${element.outerHTML}
//                 </div>
//                 <div style="margin-top: 20px; text-align: center; font-size: 12px; color: #666;">
//                     Generated on ${new Date().toLocaleDateString()} at ${new Date().toLocaleTimeString()}
//                 </div>
//             </div>
//         `;

//         const opt = {
//             margin: [10, 10, 10, 10],
//             filename: `organization-chart-${new Date().toISOString().split('T')[0]}.pdf`,
//             image: { 
//                 type: 'jpeg', 
//                 quality: 0.95 
//             },
//             html2canvas: { 
//                 scale: 1.5,
//                 useCORS: true,
//                 allowTaint: true,
//                 scrollX: 0,
//                 scrollY: 0,
//                 width: element.scrollWidth,
//                 height: element.scrollHeight,
//                 backgroundColor: '#ffffff',
//                 logging: false,
//                 imageTimeout: 15000
//             },
//             jsPDF: { 
//                 unit: 'mm', 
//                 format: 'a4', 
//                 orientation: 'landscape',
//                 compress: true
//             },
//             pagebreak: { 
//                 mode: ['avoid-all', 'css', 'legacy'] 
//             }
//         };

//         // Generate PDF
//         await html2pdf()
//             .set(opt)
//             .from(wrapper)
//             .save();
            
//         console.log('PDF generated successfully');
        
//     } catch (error) {
//         console.error('PDF generation failed:', error);
//         alert('PDF generation failed: ' + error.message);
//     } finally {
//         $('#pdf-loading-alt').remove();
//     }
// }

// Debug function to check images
function debugImages() {
    console.log('=== IMAGE DEBUG INFO ===');
    const images = document.querySelectorAll('#tree img, #tree image');
    console.log('Total images found:', images.length);
    
    images.forEach((img, index) => {
        if (img.tagName.toLowerCase() === 'img') {
            console.log(`IMG ${index}:`, {
                src: img.src,
                complete: img.complete,
                naturalWidth: img.naturalWidth,
                naturalHeight: img.naturalHeight,
                crossOrigin: img.crossOrigin
            });
        } else {
            const href = img.getAttribute('xlink:href') || img.getAttribute('href');
            console.log(`SVG IMAGE ${index}:`, {
                href: href,
                tagName: img.tagName
            });
        }
    });
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
    // $('#exportPdfBtn').click(function(e) {
    //     e.preventDefault();
    //     exportToPDF();
    // });

    // Alternative PDF export button
    // $('#exportPdfAlternativeBtn').click(function(e) {
    //     e.preventDefault();
    //     exportToPDFAlternative();
    // });

    // Canvas-based PDF export (most reliable)
    $('#exportPdfCanvasBtn').click(function(e) {
        e.preventDefault();
        exportToPDFCanvas();
    });

    // Debug button for testing images
    $('#debugImagesBtn').click(function(e) {
        e.preventDefault();
        debugImages();
    });

    // Debug: Add console logging
    console.log('Chart initialization complete');
    
    // Test chart object
    window.testChart = function() {
        console.log('Chart object:', chart);
        if (chart) {
            console.log('Chart nodes:', chart.config.nodes);
        }
    };
    
    // Global debug function
    window.debugImages = debugImages;
});
</script>
@endsection
