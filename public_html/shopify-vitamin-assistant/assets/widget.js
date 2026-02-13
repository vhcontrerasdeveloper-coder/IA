(function () {
  const cfg = window.VITAMIN_CHAT_CONFIG || {};
  const endpoint = cfg.endpoint;
  const apiKey = cfg.apiKey;

  if (!endpoint || !apiKey) {
    console.warn('Vitamin assistant: falta endpoint o apiKey');
    return;
  }

  const style = document.createElement('style');
  style.textContent = `
    .va-chat-btn{position:fixed;right:24px;bottom:24px;z-index:9999;background:#1f7a4c;color:#fff;border:none;border-radius:999px;padding:14px 18px;font-weight:700;cursor:pointer;box-shadow:0 6px 18px rgba(0,0,0,.2)}
    .va-chat{position:fixed;right:24px;bottom:84px;width:340px;max-width:calc(100vw - 24px);height:500px;background:#fff;border-radius:16px;box-shadow:0 12px 40px rgba(0,0,0,.22);display:none;z-index:9999;overflow:hidden;border:1px solid #e8ecea}
    .va-header{padding:12px 14px;background:#1f7a4c;color:#fff;font-weight:700}
    .va-messages{height:390px;overflow:auto;padding:12px;background:#f7fbf8;font-size:14px}
    .va-msg{margin-bottom:10px;padding:10px;border-radius:10px;line-height:1.4}
    .va-user{background:#e7f5ed;margin-left:30px}
    .va-bot{background:#fff;border:1px solid #e6ece8;margin-right:30px}
    .va-input-wrap{display:flex;gap:8px;padding:10px;border-top:1px solid #e9efeb;background:#fff}
    .va-input{flex:1;padding:10px;border:1px solid #cad9d0;border-radius:10px}
    .va-send{background:#1f7a4c;color:#fff;border:none;padding:10px 14px;border-radius:10px;cursor:pointer}
  `;
  document.head.appendChild(style);

  const btn = document.createElement('button');
  btn.className = 'va-chat-btn';
  btn.textContent = cfg.buttonText || 'Asesor de Salud';

  const panel = document.createElement('div');
  panel.className = 'va-chat';
  panel.innerHTML = `
    <div class="va-header">${cfg.title || 'Asistente Salud Natural'}</div>
    <div class="va-messages" id="vaMessages">
      <div class="va-msg va-bot">Hola 👋 Soy tu asistente de vitaminas. Cuéntame tus síntomas o tu objetivo (energía, sueño, defensas, digestión...) y te recomiendo productos de la tienda.</div>
    </div>
    <div class="va-input-wrap">
      <input id="vaInput" class="va-input" placeholder="Ej. Tengo cansancio y bajo ánimo" />
      <button id="vaSend" class="va-send">Enviar</button>
    </div>
  `;

  let history = [];

  const messages = panel.querySelector('#vaMessages');
  const input = panel.querySelector('#vaInput');
  const send = panel.querySelector('#vaSend');

  function addMessage(content, role) {
    const div = document.createElement('div');
    div.className = `va-msg ${role === 'user' ? 'va-user' : 'va-bot'}`;
    div.innerHTML = content.replace(/\n/g, '<br>');
    messages.appendChild(div);
    messages.scrollTop = messages.scrollHeight;
  }

  async function sendMessage() {
    const text = input.value.trim();
    if (!text) return;

    input.value = '';
    addMessage(text, 'user');
    history.push({ role: 'user', content: text });

    try {
      const res = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Api-Key': apiKey,
        },
        body: JSON.stringify({ message: text, history }),
      });
      const data = await res.json();
      const answer = data.answer || 'No pude responder en este momento.';
      addMessage(answer, 'assistant');
      history.push({ role: 'assistant', content: answer });
    } catch (e) {
      addMessage('Hubo un problema de conexión. Intenta nuevamente.', 'assistant');
    }
  }

  btn.addEventListener('click', () => {
    panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
  });
  send.addEventListener('click', sendMessage);
  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') sendMessage();
  });

  document.body.appendChild(btn);
  document.body.appendChild(panel);
})();
