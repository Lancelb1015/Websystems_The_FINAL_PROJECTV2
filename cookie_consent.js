(function () {
  function getCookie(name) {
    return document.cookie.split(";").map(s => s.trim()).find(s => s.startsWith(name + "="));
  }

  if (getCookie("alphatech_cookie_consent")) return;

  const bar = document.createElement("div");
  bar.style.cssText = [
    "position:fixed",
    "left:16px",
    "right:16px",
    "bottom:16px",
    "background:#111",
    "color:#fff",
    "padding:14px 16px",
    "border-radius:12px",
    "display:flex",
    "gap:12px",
    "align-items:center",
    "justify-content:space-between",
    "box-shadow:0 6px 22px rgba(0,0,0,0.25)",
    "z-index:9999",
    "font-family:Arial, sans-serif",
    "font-size:13px"
  ].join(";");

  const text = document.createElement("div");
  text.textContent = "We use cookies to keep you signed in and improve your experience.";

  const btn = document.createElement("button");
  btn.textContent = "Accept";
  btn.style.cssText = [
    "background:#4c5bd4",
    "border:none",
    "color:#fff",
    "padding:10px 14px",
    "border-radius:10px",
    "cursor:pointer",
    "font-size:13px"
  ].join(";");

  btn.addEventListener("click", function () {
    const d = new Date();
    d.setFullYear(d.getFullYear() + 1);
    document.cookie = "alphatech_cookie_consent=1; expires=" + d.toUTCString() + "; path=/; SameSite=Lax";
    bar.remove();
  });

  bar.appendChild(text);
  bar.appendChild(btn);
  document.body.appendChild(bar);
})();

