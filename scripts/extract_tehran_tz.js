// Extract Asia/Tehran UTC-offset transitions (1900-2040) from the IANA tzdata bundled with Node/ICU.
// Usage: node scripts/extract_tehran_tz.js > reference/tehran-tz.json
const f = new Intl.DateTimeFormat('en-US', { timeZone: 'Asia/Tehran', timeZoneName: 'longOffset' });
const off = (t) => {
  const s = f.formatToParts(new Date(t)).find((p) => p.type === 'timeZoneName').value;
  if (s === 'GMT') return 0;
  const m = s.match(/GMT([+-])(\d\d):(\d\d)/);
  return (m[1] === '-' ? -1 : 1) * (+m[2] * 60 + +m[3]);
};
const H = 3600e3, end = Date.UTC(2040, 0, 1);
let t = Date.UTC(1900, 0, 1), prev = off(t);
const out = [[null, prev]];
for (; t < end; t += 6 * H) {
  const o = off(t + 6 * H);
  if (o === prev) continue;
  let a = t, b = t + 6 * H;
  while (b - a > 60e3) { const m = Math.floor((a + b) / 2 / 60e3) * 60e3; if (off(m) === prev) a = m; else b = m; }
  out.push([b, o]); prev = o;
}
process.stderr.write(`tzdata ${process.versions.tz}: ${out.length - 1} transitions\n`);
console.log(JSON.stringify(out));
