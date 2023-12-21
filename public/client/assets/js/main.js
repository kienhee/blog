window.addEventListener("load", function () {
  setTimeout(function () {
    document.getElementById("preloader").classList.add("hide");
  }, 1000);
});
var swiper = new Swiper(".mySwiper", {
  effect: "cards",
  grabCursor: true,
});
// Project and Testimonial carousel
$(".blog__home-carousel, .testimonial-carousel").owlCarousel({
  autoplay: true,
  smartSpeed: 1000,
  margin: 25,
  loop: true,
  center: true,
  dots: false,
  nav: true,
  navText: [
    '<i class="fa-solid fa-chevron-left"></i>',
    '<i class="fa-solid fa-chevron-right"></i>',
  ],
  responsive: {
    0: {
      items: 1,
    },
    576: {
      items: 1,
    },
    768: {
      items: 2,
    },
    992: {
      items: 3,
    },
  },
});
$(window).on("load", function () {
  $(".portfolio-filter ul li").on("click", function () {
    $(".portfolio-filter ul li").removeClass("active");
    $(this).addClass("active");

    var data = $(this).attr("data-filter");
    $workGrid.isotope({
      filter: data,
    });
  });

  if (document.getElementById("portfolio")) {
    var $workGrid = $(".portfolio-grid").isotope({
      itemSelector: ".all",
      percentPosition: true,
      masonry: {
        columnWidth: ".all",
      },
    });
  }
});
if ($(".img-gal").length > 0) {
  $(".img-gal").magnificPopup({
    type: "image",
    gallery: {
      enabled: true,
    },
  });
}
