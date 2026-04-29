(function () {
  const storage = {
    get(key) {
      try {
        return window.localStorage.getItem(key);
      } catch {
        return null;
      }
    },
    set(key, value) {
      try {
        window.localStorage.setItem(key, value);
      } catch {
        return;
      }
    }
  };

  const getDeviceType = () => {
    const width = window.innerWidth;
    if (width <= 768) {
      return 'mobile';
    }
    if (width <= 1024) {
      return 'tablet';
    }
    return 'desktop';
  };

  const isNewVisitor = () => {
    const key = 'popuppilot_seen_visitor';
    const seen = storage.get(key);
    if (seen) {
      return false;
    }
    storage.set(key, '1');
    return true;
  };

  const matchRegex = (pattern, value) => {
    try {
      return new RegExp(pattern).test(value);
    } catch {
      return false;
    }
  };

  const evaluateTargeting = (rules) => {
    if (!rules || typeof rules !== 'object') {
      return true;
    }

    if (Array.isArray(rules.device) && rules.device.length > 0) {
      if (!rules.device.includes(getDeviceType())) {
        return false;
      }
    }

    if (typeof rules.urlRegex === 'string' && rules.urlRegex !== '') {
      if (!matchRegex(rules.urlRegex, window.location.href)) {
        return false;
      }
    }

    if (typeof rules.referrerRegex === 'string' && rules.referrerRegex !== '') {
      if (!matchRegex(rules.referrerRegex, document.referrer || '')) {
        return false;
      }
    }

    if (typeof rules.loggedIn === 'boolean') {
      const body = document.body;
      const loggedIn = body.classList.contains('logged-in');
      if (loggedIn !== rules.loggedIn) {
        return false;
      }
    }

    if (rules.visitorType === 'new' && !isNewVisitor()) {
      return false;
    }

    if (rules.visitorType === 'returning' && isNewVisitor()) {
      return false;
    }

    return true;
  };

  const canShowByFrequency = (popup) => {
    const freq = popup.frequency || {};
    const baseKey = `popuppilot_freq_${String(popup.id)}`;
    const now = Date.now();

    if (freq.oncePerSession) {
      if (window.sessionStorage.getItem(baseKey) === '1') {
        return false;
      }
    }

    if (typeof freq.oncePerDays === 'number' && freq.oncePerDays > 0) {
      const last = storage.get(`${baseKey}_last`);
      if (last) {
        const diffDays = (now - Number(last)) / 86400000;
        if (diffDays < freq.oncePerDays) {
          return false;
        }
      }
    }

    if (typeof freq.maxImpressions === 'number' && freq.maxImpressions > 0) {
      const count = Number(storage.get(`${baseKey}_count`) || '0');
      if (count >= freq.maxImpressions) {
        return false;
      }
    }

    return true;
  };

  const markFrequency = (popup) => {
    const freq = popup.frequency || {};
    const baseKey = `popuppilot_freq_${String(popup.id)}`;
    const now = Date.now();

    if (freq.oncePerSession) {
      window.sessionStorage.setItem(baseKey, '1');
    }

    const count = Number(storage.get(`${baseKey}_count`) || '0');
    storage.set(`${baseKey}_count`, String(count + 1));
    storage.set(`${baseKey}_last`, String(now));
  };

  const popupRulesPass = (popup) => {
    return evaluateTargeting(popup.targeting) && canShowByFrequency(popup);
  };

  const attachTriggers = (popup, show) => {
    const trigger = popup.trigger || { type: 'page_load' };

    if (trigger.type === 'page_load') {
      show();
      return;
    }

    if (trigger.type === 'time_delay') {
      window.setTimeout(show, Number(trigger.delayMs || 1000));
      return;
    }

    if (trigger.type === 'scroll_percentage') {
      const threshold = Number(trigger.percent || 50);
      const onScroll = () => {
        const scrolled = (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100;
        if (scrolled >= threshold) {
          window.removeEventListener('scroll', onScroll);
          show();
        }
      };
      window.addEventListener('scroll', onScroll, { passive: true });
      return;
    }

    if (trigger.type === 'exit_intent') {
      const onLeave = (event) => {
        if (event.clientY <= 0) {
          document.removeEventListener('mouseout', onLeave);
          show();
        }
      };
      document.addEventListener('mouseout', onLeave);
      return;
    }

    if (trigger.type === 'inactivity') {
      let timer = null;
      const ms = Number(trigger.ms || 30000);
      const reset = () => {
        if (timer) {
          window.clearTimeout(timer);
        }
        timer = window.setTimeout(show, ms);
      };
      ['mousemove', 'keydown', 'scroll', 'click'].forEach((eventName) => {
        window.addEventListener(eventName, reset, { passive: true });
      });
      reset();
      return;
    }

    if (trigger.type === 'click_selector' && typeof trigger.selector === 'string') {
      document.addEventListener('click', (event) => {
        const target = event.target;
        if (target instanceof Element && target.matches(trigger.selector)) {
          show();
        }
      });
      return;
    }

    if (trigger.type === 'url_match') {
      if (typeof trigger.pattern === 'string' && matchRegex(trigger.pattern, window.location.href)) {
        show();
      }
      return;
    }

    if (trigger.type === 'referrer_match') {
      if (typeof trigger.pattern === 'string' && matchRegex(trigger.pattern, document.referrer || '')) {
        show();
      }
      return;
    }

    show();
  };

  const createNode = (tag, style) => {
    const node = document.createElement(tag);
    Object.assign(node.style, style || {});
    return node;
  };

  const renderComponent = (component) => {
    const box = createNode('div', {
      position: 'absolute',
      left: `${String(component.x || 0)}px`,
      top: `${String(component.y || 0)}px`,
      zIndex: String(component.zIndex || 1),
      width: component.width ? `${String(component.width)}px` : 'auto',
      height: component.height ? `${String(component.height)}px` : 'auto',
      display: component.hidden ? 'none' : 'block'
    });

    const props = component.props || {};

    if (component.type === 'text') {
      box.textContent = typeof props.text === 'string' ? props.text : '';
      return box;
    }

    if (component.type === 'image') {
      const image = document.createElement('img');
      image.src = typeof props.src === 'string' ? props.src : '';
      image.alt = typeof props.alt === 'string' ? props.alt : '';
      image.style.maxWidth = '100%';
      box.appendChild(image);
      return box;
    }

    if (component.type === 'button') {
      const button = document.createElement('a');
      button.href = typeof props.href === 'string' ? props.href : '#';
      button.textContent = typeof props.label === 'string' ? props.label : 'Click';
      button.style.display = 'inline-block';
      button.style.padding = '8px 12px';
      button.style.background = '#0d6efd';
      button.style.color = '#fff';
      button.style.textDecoration = 'none';
      box.appendChild(button);
      return box;
    }

    if (component.type === 'form') {
      const form = document.createElement('form');
      const fields = Array.isArray(props.fields) ? props.fields : [];
      fields.forEach((field) => {
        const input = document.createElement('input');
        input.name = typeof field.name === 'string' ? field.name : '';
        input.placeholder = typeof field.label === 'string' ? field.label : '';
        input.type = typeof field.type === 'string' ? field.type : 'text';
        input.required = field.required === true;
        input.style.display = 'block';
        input.style.marginBottom = '8px';
        form.appendChild(input);
      });
      const submit = document.createElement('button');
      submit.type = 'submit';
      submit.textContent = 'Submit';
      form.appendChild(submit);
      box.appendChild(form);
      return box;
    }

    if (component.type === 'countdown') {
      const label = document.createElement('div');
      const target = typeof props.endsAt === 'string' ? Date.parse(props.endsAt) : NaN;
      const tick = () => {
        if (Number.isNaN(target)) {
          label.textContent = 'Invalid timer';
          return;
        }
        const diff = Math.max(0, target - Date.now());
        const secs = Math.floor(diff / 1000) % 60;
        const mins = Math.floor(diff / 60000) % 60;
        const hrs = Math.floor(diff / 3600000);
        label.textContent = `${String(hrs)}h ${String(mins)}m ${String(secs)}s`;
      };
      tick();
      window.setInterval(tick, 1000);
      box.appendChild(label);
      return box;
    }

    if (component.type === 'video') {
      const frame = document.createElement('iframe');
      frame.src = typeof props.url === 'string' ? props.url : '';
      frame.width = '100%';
      frame.height = '100%';
      frame.allow = 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture';
      frame.referrerPolicy = 'strict-origin-when-cross-origin';
      box.appendChild(frame);
      return box;
    }

    return box;
  };

  const renderPopup = (popup) => {
    const documentData = popup.document;
    if (!documentData || !Array.isArray(documentData.steps) || documentData.steps.length === 0) {
      return;
    }

    const firstStep = documentData.steps[0];
    const components = Array.isArray(firstStep.components) ? firstStep.components : [];

    const container = createNode('div', {
      position: 'fixed',
      right: '20px',
      bottom: '20px',
      width: '320px',
      minHeight: '160px',
      background: '#fff',
      border: '1px solid #d3dce3',
      boxShadow: '0 8px 24px rgba(0,0,0,0.12)',
      zIndex: '999999',
      overflow: 'hidden'
    });

    const stage = createNode('div', { position: 'relative', minHeight: '160px', padding: '12px' });
    components.forEach((component) => {
      stage.appendChild(renderComponent(component));
    });

    container.appendChild(stage);
    document.body.appendChild(container);
    markFrequency(popup);
  };

  const config = window.PopupPilotRuntime;
  if (!config || !Array.isArray(config.popups) || config.popups.length === 0) {
    return;
  }

  const eligible = config.popups
    .filter(popupRulesPass)
    .sort((a, b) => Number(b.priority || 0) - Number(a.priority || 0));

  const winner = eligible[0];
  if (!winner) {
    return;
  }

  attachTriggers(winner, () => {
    renderPopup(winner);
  });
})();
