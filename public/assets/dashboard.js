(function () {
  const root = document.querySelector('.wrap');
  const key = root?.dataset?.publicKey || '';
  const baseUrl = root?.dataset?.baseUrl || '';

  const elTitle = document.getElementById('title');
  const elStatus = document.getElementById('status');
  const elScrutiny = document.getElementById('scrutiny');
  const elVotes = document.getElementById('votes');
  const elExpected = document.getElementById('expected');
  const elRemaining = document.getElementById('remaining');
  const elBarFill = document.getElementById('barFill');
  const elUpdatedAt = document.getElementById('updatedAt');
  const elResult = document.getElementById('result');
  const elSelect = document.getElementById('electionSelect');

  let timer = null;

  function fmtDate(iso) {
    try {
      return new Date(iso).toLocaleString('pt-BR');
    } catch {
      return '';
    }
  }

  async function loadElectionsList() {
    if (!elSelect) return;
    try {
      const res = await fetch(`${baseUrl}/api/public_elections.php`, {
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
      });
      if (!res.ok) return;
      const data = await res.json();
      
      elSelect.innerHTML = '<option value="">-- Selecione uma Eleição --</option>';
      data.elections.forEach(e => {
        const option = document.createElement('option');
        option.value = e.public_key;
        const churchName = e.church_name ? `[${e.church_name}] ` : '';
        option.textContent = `${churchName}${e.title} (${fmtDate(e.election_date).split(' ')[0]}) - ${e.status === 'OPEN' ? 'Aberta' : 'Encerrada'}`;
        if (e.public_key === key) option.selected = true;
        elSelect.appendChild(option);
      });

      elSelect.addEventListener('change', function() {
        if (this.value) {
          window.location.href = `${baseUrl}/dashboard.php?key=${encodeURIComponent(this.value)}`;
        } else {
          window.location.href = `${baseUrl}/dashboard.php`;
        }
      });
    } catch (err) {
      console.error('Erro ao carregar lista de eleições', err);
      elSelect.innerHTML = '<option value="">Erro ao carregar</option>';
    }
  }

  function setStatus(label, kind) {
    elStatus.textContent = label;
    elStatus.classList.remove('ok', 'warn', 'danger');
    if (kind) elStatus.classList.add(kind);
  }

  function renderResult(data) {
    elResult.innerHTML = '';

    if (!data) {
      elResult.innerHTML = '<div class="muted">Aguardando apuração…</div>';
      return;
    }

    if (data.type === 'PASTOR') {
      const rows = [
        { k: 'SIM', v: data.pastor.counts.SIM },
        { k: 'NÃO', v: data.pastor.counts.NAO },
        { k: 'BRANCO', v: data.pastor.counts.BRANCO },
      ];

      const quorum = data.pastor.quorum_50p1 || 0;
      elResult.innerHTML += `<div class="muted" style="margin-bottom:10px;">Quórum 50%+1: <strong>${quorum}</strong> votos</div>`;

      rows.forEach(r => {
        const div = document.createElement('div');
        div.className = 'row';
        div.innerHTML = `<div>${r.k}</div><div><strong>${r.v}</strong></div>`;
        elResult.appendChild(div);
      });

      if (data.final || data.scrutiny_closed) {
        const badge = document.createElement('div');
        badge.className = 'row';
        const status = data.pastor.final_status === 'ELEITO' ? 'ok' : 'danger';
        const txt = data.pastor.final_status === 'ELEITO' ? 'Eleito' : 'Não Eleito';
        badge.innerHTML = `<div>Status Final</div><div class="badge ${status}">${txt}</div>`;
        elResult.appendChild(badge);
      } else {
        elResult.innerHTML += '<div class="muted" style="margin-top:10px;">Apuração em tempo real...</div>';
      }
      return;
    }

    if (data.type === 'OFICIAIS') {
      const quorum = data.officers.quorum_50p1 || 0;
      elResult.innerHTML += `<div class="muted" style="margin-bottom:10px;">Quórum 50%+1: <strong>${quorum}</strong> votos</div>`;

      if (data.officers.live_votes && data.officers.live_votes.length > 0) {
        data.officers.live_votes.forEach(lv => {
          if (lv.votes === 0 && lv.candidate_status && lv.candidate_status !== 'ACTIVE' && lv.name !== 'BRANCOS') {
            return;
          }
          const div = document.createElement('div');
          div.className = 'row';
          const isElectedLive = lv.votes >= quorum && lv.name !== 'BRANCOS';
          const liveBadge = isElectedLive ? `<span style="font-size:10px; color:var(--ok); margin-left:5px; font-weight:bold;">[ELEITO]</span>` : '';
          div.innerHTML = `<div>${lv.name} ${liveBadge}</div><div><strong>${lv.votes}</strong></div>`;
          elResult.appendChild(div);
        });
      }

      const hasElected = Array.isArray(data.officers.elected) && data.officers.elected.length > 0;

      if (data.final || data.scrutiny_closed || hasElected) {
        elResult.innerHTML += '<h3 style="font-size:14px; margin-top:20px; margin-bottom:10px;">Status de Eleitos (Geral)</h3>';
        if (!hasElected) {
          elResult.innerHTML += '<div class="muted">Nenhum eleito registrado até o momento.</div>';
        } else {
          data.officers.elected.forEach(e => {
            const div = document.createElement('div');
            div.className = 'row';
            const badgeKind = e.rule === 'MAIORIA_SIMPLES' ? 'warn' : 'ok';
            const badgeTxt = e.rule === 'MAIORIA_SIMPLES' ? 'Maioria simples' : '50% + 1';
            div.innerHTML = `<div>${e.full_name}</div><div class="badge ${badgeKind}">${badgeTxt}</div>`;
            elResult.appendChild(div);
          });
        }
      } 
      
      if (!data.final && !data.scrutiny_closed) {
        elResult.innerHTML += '<div class="muted" style="margin-top:10px;">Apuração em tempo real...</div>';
      }
      return;
    }

    elResult.innerHTML = '<div class="muted">Sem resultado.</div>';
  }

  async function fetchData() {
    if (!key) {
      setStatus('Link inválido', 'danger');
      return;
    }

    const res = await fetch(`${baseUrl}/api/public_dashboard.php?key=${encodeURIComponent(key)}`, {
      headers: { 'Accept': 'application/json' },
      cache: 'no-store'
    });

    if (!res.ok) {
      setStatus('Indisponível', 'danger');
      return;
    }

    const data = await res.json();

    elTitle.textContent = data.election.title || 'Eleição';
    elScrutiny.textContent = data.scrutiny ? `Escrutínio ${data.scrutiny.number}` : '';

    elVotes.textContent = String(data.progress.vote_count ?? 0);
    elExpected.textContent = String(data.progress.expected_voters ?? 0);

    const remaining = Math.max(0, (data.progress.expected_voters ?? 0) - (data.progress.vote_count ?? 0));
    elRemaining.textContent = String(remaining);

    const pct = (data.progress.expected_voters ?? 0) > 0
      ? Math.min(100, Math.round(((data.progress.vote_count ?? 0) / data.progress.expected_voters) * 100))
      : 0;

    elBarFill.style.width = `${pct}%`;
    elUpdatedAt.textContent = data.updated_at ? `Atualizado em ${fmtDate(data.updated_at)}` : '';

    if (data.election.status === 'CLOSED') {
      setStatus('Eleição Encerrada', 'ok');
      data.result.final = true;
      renderResult(data.result);
      if (timer) {
        clearInterval(timer);
        timer = null;
      }
      return;
    }

    if (data.scrutiny && data.scrutiny.status === 'CLOSED') {
      setStatus('Escrutínio Encerrado', 'ok');
      data.result.scrutiny_closed = true;
      renderResult(data.result);
      return;
    }

    setStatus('Em andamento', 'warn');
    renderResult(data.result);
  }

  loadElectionsList();
  
  if (key) {
    fetchData();
    timer = setInterval(fetchData, 2000);
  }
})();