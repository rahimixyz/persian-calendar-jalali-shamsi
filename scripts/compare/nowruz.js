const ref = require('../../reference/nowruz.json');
const pad = (n, w = 2) => String(n).padStart(w, '0');
const iso = (y, m, d) => `${pad(y, 4)}-${pad(m)}-${pad(d)}`;
const fromDate = (dt) => iso(dt.getUTCFullYear(), dt.getUTCMonth() + 1, dt.getUTCDate());
process.env.TZ = 'UTC';
const libs = {};
const jj = require('jalaali-js');
libs['jalaali-js'] = (jy) => { const g = jj.toGregorian(jy, 1, 1); return iso(g.gy, g.gm, g.gd); };
const mj = require('moment-jalaali');
libs['moment-jalaali'] = (jy) => { const m = mj(`${jy}/1/1`, 'jYYYY/jM/jD'); return m.isValid() ? m.format('YYYY-MM-DD') : null; };
const jm = require('jalali-moment');
libs['jalali-moment'] = (jy) => { const m = jm.from(`${jy}/01/01`, 'fa', 'YYYY/MM/DD'); return m.isValid() ? m.locale('en').format('YYYY-MM-DD') : null; };
const dfj = require('date-fns-jalali');
libs['date-fns-jalali'] = (jy) => { const d = dfj.newDate(jy, 0, 1); return isNaN(d) ? null : iso(d.getFullYear(), d.getMonth() + 1, d.getDate()); };
const dayjs = require('dayjs'); { const j = require('jalaliday'); dayjs.extend(j.default || j); }
libs['jalaliday'] = (jy) => { const d = dayjs(`${jy}-01-01`, { jalali: true }); return d.isValid() ? d.calendar('gregory').format('YYYY-MM-DD') : null; };
const PD = require('persian-date');
libs['persian-date'] = (jy) => { const d = new PD([jy, 1, 1]).toDate(); return isNaN(d) ? null : iso(d.getFullYear(), d.getMonth() + 1, d.getDate()); };
const fmt = new Intl.DateTimeFormat('en-u-ca-persian-nu-latn', { timeZone: 'UTC', year: 'numeric', month: 'numeric', day: 'numeric' });
libs['Intl (ICU ' + process.versions.icu + ')'] = (jy) => {
  for (let d = 18; d <= 24; d++) {
    const dt = new Date(0); dt.setUTCFullYear(jy + 621, 2, d); dt.setUTCHours(12);
    const p = Object.fromEntries(fmt.formatToParts(dt).map(x => [x.type, x.value]));
    if (+p.month === 1 && +p.day === 1 && parseInt(p.year) === jy) return fromDate(dt);
  }
  return null;
};
const out = {};
for (const [name, f] of Object.entries(libs)) {
  const bad = []; let errors = 0, checked = 0;
  for (const r of ref) {
    let v; try { v = f(r.jy); } catch (e) { v = 'ERR'; }
    if (v === 'ERR' || v === null) { errors++; continue; }
    checked++;
    if (v !== r.nowruz) bad.push({ jy: r.jy, got: v, want: r.nowruz, uncertain: r.uncertain });
  }
  out[name] = { checked, errors, bad };
  const near = bad.filter(b => b.jy >= 1300 && b.jy <= 1500);
  const certainBad = bad.filter(b => !b.uncertain);
  console.log(name.padEnd(22), 'checked', checked, 'errors', errors, '| wrong (certain years):', certainBad.length,
    '| wrong in 1300-1500:', near.map(b => `${b.jy}(${b.got}≠${b.want})`).join(' ') || 'none');
}
require('fs').writeFileSync(__dirname + '/results-js.json', JSON.stringify(out));
