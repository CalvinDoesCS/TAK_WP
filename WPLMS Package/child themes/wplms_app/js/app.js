(() => {
  // assets/js/app.js
  document.addEventListener("DOMContentLoaded", () => {
    let a = localStorage.getItem("minimise_side_header");
    if (a && window.screen.width > 768) {
      document.querySelector("body").classList.add("expanded_side_menu");
      setTimeout(() => {
        let evt = window.document.createEvent("UIEvents");
        evt.initUIEvent("resize", true, false, window, 0);
        window.dispatchEvent(evt);
      }, 320);
    } else {
      document.querySelector("body").classList.remove("expanded_side_menu");
      setTimeout(() => {
        let evt = window.document.createEvent("UIEvents");
        evt.initUIEvent("resize", true, false, window, 0);
        window.dispatchEvent(evt);
      }, 320);
    }
    document.querySelectorAll(".minimise_side_header").forEach((el) => {
      el.addEventListener("click", (e) => {
        e.preventDefault();
        if (document.querySelector("body").classList.contains("expanded_side_menu")) {
          document.querySelector("body").classList.remove("expanded_side_menu");
          localStorage.removeItem("minimise_side_header");
        } else {
          document.querySelector("body").classList.add("expanded_side_menu");
          localStorage.setItem("minimise_side_header", 1);
        }
      });
    });
    if (document.querySelector(".dark_light_switch")) {
      if (localStorage.getItem("dark_theme")) {
        document.querySelector("body").classList.add("dark_theme");
      }
      document.querySelector(".dark_light_switch").addEventListener("click", () => {
        if (document.querySelector("body").classList.contains("dark_theme")) {
          document.querySelector("body").classList.remove("dark_theme");
          localStorage.removeItem("dark_theme");
        } else {
          document.querySelector("body").classList.add("dark_theme");
          localStorage.setItem("minimise_side_header", 1);
        }
      });
    }
    if (Math.max(document.documentElement.clientWidth || 0, window.innerWidth) < 768) {
    }
    if (document.querySelector(".in_tab_radio")) {
      let id = document.querySelector(".in_tab_radio").getAttribute("id");
      if (document.querySelector(".in_tab_radio:checked")) {
        id = document.querySelector(".in_tab_radio:checked").getAttribute("id");
      }
      document.querySelector('label[for="' + id + '"]').classList.add("active");
      document.querySelector(".in_tab_details." + id).classList.add("active");
      document.querySelectorAll(".in_tab_radio").forEach((el) => {
        el.addEventListener("click", () => {
          id = document.querySelector(".in_tab_radio:checked").getAttribute("id");
          console.log("#", id);
          document.querySelector("label.in_tab.active").classList.remove("active");
          document.querySelector(".in_tab_details.active").classList.remove("active");
          document.querySelector('label[for="' + id + '"]').classList.add("active");
          document.querySelector(".in_tab_details." + id).classList.add("active");
        });
      });
    }
    if (document.querySelector(".searchbox")) {
      document.querySelector(".searchbox svg").addEventListener("click", (e) => {
        e.preventDefault();
        if (document.querySelector(".searchbox").classList.contains("active")) {
          document.querySelector(".searchbox").classList.remove("active");
        } else {
          document.querySelector(".searchbox").classList.add("active");
        }
      });
    }
  });
})();
