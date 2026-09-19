document.addEventListener("DOMContentLoaded", () => {
  const t = document.querySelector(".nav-toggle"),
    n = document.querySelector(".nav-links");
  if (t && n) t.addEventListener("click", () => n.classList.toggle("open"));
  document
    .querySelectorAll("input[type=tel]")
    .forEach((i) =>
      i.addEventListener(
        "input",
        () => (i.value = i.value.replace(/\D/g, "").slice(0, 10)),
      ),
    );
});
