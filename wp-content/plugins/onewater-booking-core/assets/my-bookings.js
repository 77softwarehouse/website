(function () {
  const root = document.querySelector('[data-onewater-my-bookings]');
  if (!root || !window.oneWaterMyBookings) return;

  const cfg = window.oneWaterMyBookings;
  const listEl = root.querySelector('[data-bookings-list]');
  const emptyEl = root.querySelector('[data-bookings-empty]');
  const dateFormatter = new Intl.DateTimeFormat('en-CA', { month: 'short', day: 'numeric', year: 'numeric' });

  function formatDate(iso) {
    if (!iso) return '—';
    return dateFormatter.format(new Date(`${iso}T00:00:00`));
  }

  function minStartDate() {
    const date = new Date();
    date.setDate(date.getDate() + (cfg.minimumNoticeDays || 0));
    return date.toISOString().slice(0, 10);
  }

  async function api(path, options) {
    const response = await fetch(`${cfg.apiBase}${path}`, Object.assign({
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
    }, options));
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(payload.message || 'Request failed.');
    return payload;
  }

  function metaItem(label, value) {
    const li = document.createElement('li');
    const strong = document.createElement('span');
    strong.className = 'ows-booking-card__meta-label';
    strong.textContent = `${label}: `;
    const span = document.createElement('span');
    span.textContent = value || '—';
    li.appendChild(strong);
    li.appendChild(span);
    return li;
  }

  function buildCard(reservation) {
    const card = document.createElement('article');
    card.className = 'ows-booking-card';
    card.dataset.id = reservation.id;

    const dates = document.createElement('div');
    dates.className = 'ows-booking-card__dates';
    dates.innerHTML = `<strong>${formatDate(reservation.start_date)}</strong> &rarr; <strong>${formatDate(reservation.checkout_date)}</strong>`;
    card.appendChild(dates);

    const meta = document.createElement('ul');
    meta.className = 'ows-booking-card__meta';
    meta.appendChild(metaItem('Status', reservation.status_label));
    meta.appendChild(metaItem('Payment', reservation.payment_status));
    meta.appendChild(metaItem('Lease', reservation.lease_status));
    card.appendChild(meta);

    const actions = document.createElement('div');
    actions.className = 'ows-booking-card__actions';
    card.appendChild(actions);

    const result = document.createElement('output');
    result.className = 'ows-booking-card__result';
    card.appendChild(result);

    if (reservation.status === 'cancelled') {
      const tag = document.createElement('span');
      tag.className = 'ows-booking-card__tag';
      tag.textContent = 'Cancelled';
      actions.appendChild(tag);
      return card;
    }

    if (reservation.modifiable) {
      const label = document.createElement('label');
      label.className = 'ows-booking-card__modify';
      label.textContent = 'Change start date';

      const input = document.createElement('input');
      input.type = 'date';
      input.value = reservation.start_date;
      input.min = minStartDate();
      label.appendChild(input);
      actions.appendChild(label);

      const save = document.createElement('button');
      save.type = 'button';
      save.className = 'ows-button';
      save.textContent = 'Save change';
      save.addEventListener('click', async () => {
        result.textContent = 'Saving change...';
        try {
          await api(`/reservations/${reservation.id}`, {
            method: 'PATCH',
            body: JSON.stringify({ start_date: input.value }),
          });
          result.textContent = 'Updated. Refreshing...';
          load();
        } catch (error) {
          result.textContent = error.message;
        }
      });
      actions.appendChild(save);
    } else {
      const note = document.createElement('p');
      note.className = 'ows-booking-card__note';
      note.textContent = 'To change a confirmed stay, please contact the manager.';
      actions.appendChild(note);
    }

    const cancel = document.createElement('button');
    cancel.type = 'button';
    cancel.className = 'ows-button ows-button--ghost';
    cancel.textContent = 'Cancel booking';
    cancel.addEventListener('click', async () => {
      if (!window.confirm('Cancel this booking? A manager will be notified.')) return;
      result.textContent = 'Cancelling...';
      try {
        await api(`/reservations/${reservation.id}/cancel`, { method: 'POST', body: '{}' });
        result.textContent = 'Cancelled. Refreshing...';
        load();
      } catch (error) {
        result.textContent = error.message;
      }
    });
    actions.appendChild(cancel);

    return card;
  }

  async function load() {
    listEl.textContent = 'Loading your reservations...';
    try {
      const data = await api('/my-reservations', { method: 'GET' });
      const reservations = data.reservations || [];
      listEl.innerHTML = '';

      if (!reservations.length) {
        emptyEl.hidden = false;
        return;
      }

      emptyEl.hidden = true;
      reservations.forEach((reservation) => listEl.appendChild(buildCard(reservation)));
    } catch (error) {
      listEl.textContent = error.message;
    }
  }

  load();
})();
