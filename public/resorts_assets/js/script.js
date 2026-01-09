$(function () {
  $(".lazy").lazy();
});
$(function () {
  $('[data-toggle="tooltip"]').tooltip();
});

$(document).ready(function () {
  $(".select2").select2();

  $(".select2t-none").select2({
    // minimumResultsForSearch: -1,
    // minimumInputLength: 3,
    allowClear: true, // Option to clear selection
  });

  $('.modal').on('shown.bs.modal', function (e) {
    var modal = $(this); // Get the current modal
    modal.find('.select2-modal').select2({
      dropdownParent: modal
    });
  });


  // navigation and icon toggle*************************************************************start
  // Function to handle navigation and icon toggle
  function toggleNavigation() {
    if ($("#toggle-check").is(":checked")) {
      $(".navigation-wrapper").addClass("left-0"); // Show the navigation
      $(".toggle-icon").addClass("cross-icon"); // Change to cross icon
    } else {
      $(".navigation-wrapper").removeClass("left-0"); // Hide the navigation
      $(".toggle-icon").removeClass("cross-icon"); // Change back to hamburger menu
    }
  }

  // Listen for checkbox change to toggle navigation and icon
  $("#toggle-check").on("change", function () {
    toggleNavigation();
  });

  // Handle clicks on the body to close the navigation if it's open
  $(document).on("click", function (event) {
    if (!$(event.target).closest(".navigation-wrapper, #toggle-icon2").length) {
      if ($(".navigation-wrapper").hasClass("left-0")) {
        $("#toggle-check").prop("checked", false); // Uncheck the checkbox
        toggleNavigation(); // Update the navigation and icon
      }
    }
  });

  // Ensure the checkbox and navigation work correctly when clicking directly on the checkbox
  $("#toggle-icon2").on("click", function (event) {
    // Prevent the click from propagating to document click handler
    event.stopPropagation();
  });

  // navigation and icon toggle***********************************************************end
  // navigation and icon toggle*************************************************************start


  // navigation and icon toggle***********************************************************end

  // $(".datepicker").datepicker({
  //     format: "dd/mm/yyyy",
  // });
  // $(".nav-staffShop .carosel-menu").slick({
  //   dots: false,
  //   infinite: false,
  //   speed: 500,
  //   slidesToShow: 3,
  //   slidesToScroll: 1,
  //   // variableWidth: true,
  //   responsive: [
  //     {
  //       breakpoint: 1399,
  //       settings: {
  //         slidesToShow: 3,
  //       },
  //     },
  //     {
  //       breakpoint: 1199,
  //       settings: {
  //         slidesToShow: 1,
  //         // centerMode: true,
  //         variableWidth: true,
  //         arrows: false,
  //       },
  //     },
  //     {
  //       breakpoint: 991,
  //       settings: {
  //         slidesToShow: 1,
  //         // centerMode: true,
  //         variableWidth: true
  //       },
  //     },
  //     {
  //       breakpoint: 767,
  //       settings: {
  //         slidesToShow: 1,
  //         // centerMode: true,
  //         variableWidth: true
  //       },
  //     },
  //     {
  //       breakpoint: 575,
  //       settings: {
  //         slidesToShow: 1,
  //         // centerMode: true,
  //         variableWidth: true
  //       },
  //     },
  //   ],
  // });
  // Initialize Slick Slider
  var activeIndex = -1;
  
  // Find the index of the menu item with active class
  $('.carosel-menu .dropdown-toggle').each(function(index) {
    if ($(this).hasClass('active')) {
      activeIndex = index;
      return false; // Exit the loop once found
    }
  });
  
  
  $('.carosel-menu').slick({
    variableWidth: true,
    slidesToShow: 1,
    infinite: true,
    slidesToScroll: 3,
    initialSlide: activeIndex >= 0 ? activeIndex : 0,
    dots: false,
    focusOnSelect: false,
    swap:true
  });

// Handle active class toggle
function updateActiveClasses() {
    $('.carosel-menu .text-center').each(function () {
        if ($(this).hasClass('active')) {
            $(this).addClass('slick-current slick-active');
        } else {
            $(this).removeClass('slick-current slick-active');
        }
    });
}

// Initial call to set classes
updateActiveClasses();

// Handle click event to toggle active class
$('.carosel-menu .text-center').on('click', function () {
    $('.carosel-menu .text-center').removeClass('active');
    $(this).addClass('active');
    updateActiveClasses();
});

// Sync Slick changes with active class
$('.carosel-menu').on('afterChange', function () {
    updateActiveClasses();
});

  $(".btn-serchbox").click(function () {
    $(".serch-box").addClass("show-serch-box");
  });
  $(".close-icon").click(function () {
    $(".serch-box").removeClass("show-serch-box");
  });
  // Click outside to close the search box
  $(document).mouseup(function (e) {
    var searchBox = $(".serch-box");

    // If the target is not the search box or a child of the search box
    if (!searchBox.is(e.target) && searchBox.has(e.target).length === 0) {
      searchBox.removeClass("show-serch-box");
    }
  });
});

$(function () {
  /*var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-toggle="tooltip"]'))
      var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
      });
      */

  $('[data-toggle="tooltip"]').tooltip();
});

// carosel bropdown menu js******************************************************start
$(document).ready(function () {
  // Function to close all dropdowns
  function closeAllDropdowns() {
    $(".carosel-nav-menu").each(function () {
      if ($(this).hasClass("show")) {
        $(this).fadeOut(function () {
          $(this).removeAttr("style").removeClass("show");
        });
      }
    });
  }
  // Handle dropdown button click
  $(".carosel-menu .btn-group a").on("click", function (e) {
    // e.preventDefault();

    // e.stopPropagation(); // Prevent clicks from propagating

    var $dropdownMenu = $(this).next(".carosel-nav-menu");

    // If the dropdown is already open, close it
    if ($dropdownMenu.hasClass("show")) {
      $dropdownMenu.fadeOut(function () {
        $dropdownMenu.removeClass("show").removeAttr("style");
      });
    } else {
      // Close any other open dropdowns

      if (!$dropdownMenu.hasClass("moved-out")) {
        $("body").append($dropdownMenu);
        $dropdownMenu.addClass("moved-out");
      }
      closeAllDropdowns();

      // Position and show the dropdown
      var offset = $(this).offset();
      var buttonWidth = $(this).outerWidth();

      $dropdownMenu
        .css({
          top: offset.top + $(this).outerHeight(),
          left: offset.left,
          position: "absolute",
          "min-width": buttonWidth,
          "z-index": 9999,
        })
        .fadeIn()
        .addClass("show");
    }
  });

  // Close all dropdowns when clicking outside
  $(document).on("click", function (e) {
    if (
      !$(e.target).closest(".carosel-nav-menu, .carosel-menu .btn-group a")
        .length
    ) {
      closeAllDropdowns();
    }
  });

  // Prevent dropdown from closing when clicking inside it
  $(document).on("click", ".carosel-nav-menu", function (e) {
    e.stopPropagation();
  });
});
// carosel bropdown menu js******************************************************end

// switchtoggle
var dragItem = document.querySelector("#item");
var container = document.querySelector("#container");

var active = false;
var currentX;
var currentY;
var initialX;
var initialY;
var itemClick;
var xOffset = 0;
var yOffset = 0;


if (navigator.userAgent.toLowerCase().indexOf('firefox') > -1) {
  container.addEventListener("mouseup", dragEnd, false);
  container.addEventListener("click", toggleSwitch, false);
} else {
  container.addEventListener("touchstart", dragStart, false);
  container.addEventListener("touchend", dragEnd, false);
  container.addEventListener("touchmove", drag, false);

  container.addEventListener("mousedown", dragStart, false);
  dragItem.addEventListener("mousedown", itemDragStart, false);

  container.addEventListener("mousemove", drag, false);

  container.addEventListener("mouseup", dragEnd, false);
  container.addEventListener("click", toggleSwitch, false);
}

function dragStart(e) {
  var elm = $(this);
  var xPos = e.pageX - elm.offset().left;

  if (e.type === "touchstart") {
    var xPosMobile = e.touches[0].pageX - elm.offset().left;
    initialX = xPosMobile;
  } else {
    initialX = xPos;
  }

  dragItem.style.transition = "all .2s cubic-bezier(0.04, 0.46, 0.36, 0.99)";

  if (e.target === dragItem) {
    active = true;
  }
}

function itemDragStart(e) {
  var elm = $(this);
  var xPos = e.pageX - elm.offset().left;

  itemClick = xPos;
}

function toggleSwitch(e) {
  if (initialX > 18) {
    currentX = 0;
  } else {
    currentX = 36;
  }
}

function dragEnd(e) {
  initialX = currentX;

  active = false;

  if (initialX > 18) {
    currentX = 36;
    dragItem.style.transition = "all .2s cubic-bezier(0.04, 0.46, 0.36, 0.99)";
    container.classList.add('select-right');
    container.classList.remove('select-left');
  } else {
    currentX = 0;
    dragItem.style.transition = "all .2s cubic-bezier(0.04, 0.46, 0.36, 0.99)";
    container.classList.remove('select-right');
    container.classList.add('select-left');
  }

  setTranslate(currentX, dragItem);
}

function drag(e) {
  var elm = $(this);
  var xPos = e.pageX - elm.offset().left;
  if (!(xPos > 72 || xPos < 0)) {
    if (active) {
      e.preventDefault();

      if (e.type === "touchmove") {
        var xPosMobile = e.touches[0].pageX - elm.offset().left;
        currentX = xPosMobile - initialX;
        if (initialX > 36) {
          currentX = xPosMobile - itemClick;
        }
        if (currentX > 36) {
          currentX = 36;
          active = false;
          container.classList.add('select-right');
          container.classList.remove('select-left');
        } else if (currentX < 0) {
          currentX = 0;
          active = false;
          container.classList.remove('select-right');
          container.classList.add('select-left');
        }
      } else {
        currentX = xPos - initialX;
        if (initialX > 36) {
          currentX = xPos - itemClick;
        }
        if (currentX > 36) {
          currentX = 36;
          active = false;
          container.classList.add('select-right');
          container.classList.remove('select-left');
        } else if (currentX < 0) {
          currentX = 0;
          active = false;
          container.classList.remove('select-right');
          container.classList.add('select-left');
        }
      }

      dragItem.style.transition = "all .05s cubic-bezier(0.04, 0.46, 0.36, 0.99)";

      xOffset = currentX;

      setTranslate(currentX, dragItem);
    }
  } else {
    active = false;

    if (initialX > 36) {
      dragItem.style.transform = "translate3d(36px, 0px, 0)";
    } else {
      dragItem.style.transform = "translate3d(0, 0px, 0)";
    }
  }
}

function setTranslate(xPos, el) {
  el.style.transform = "translate3d(" + xPos + "px, 0px, 0)";
}
