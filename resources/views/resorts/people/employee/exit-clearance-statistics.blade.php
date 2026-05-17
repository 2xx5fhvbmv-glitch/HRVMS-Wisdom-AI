{{-- Row 1: Top Reasons for Leaving + Turnover Trends (half-width each).
     Row 2: Attrition Rates (full width). The cards share one Bootstrap
     `.row` (#exitInterviewCards) so 6 + 6 fills row one and the col-12
     wraps onto row two. --}}
<div class="col-xl-6 col-lg-6">
     <div class="bg-themeGrayLight h-100">
          <h6 class="fw-600 mb-3">Top Reasons for Leaving</h6>
          <canvas id="myBarReasonsChart" width="349" height="199"></canvas>
     </div>
</div>
<div class="col-xl-6 col-lg-6">
     <div class="bg-themeGrayLight h-100">
          <h6 class="fw-600 mb-3">Turnover Trends</h6>
          <canvas id="myLineChart" width="365" height="199"></canvas>
     </div>
</div>
<div class="col-12">
     <div class="bg-themeGrayLight h-100">
          <h6 class="fw-600 mb-3">Attrition Rates</h6>
          <canvas id="myBarAttrRateChart" width="824" height="199"></canvas>
     </div>
</div>
