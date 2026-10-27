import json, warnings
warnings.filterwarnings('ignore')
from importlib.metadata import version
ref = json.load(open(__import__('pathlib').Path(__file__).resolve().parents[2] / 'reference' / 'nowruz.json'))
import jdatetime, persiantools.jdatetime as pj, khayyam, convertdate.persian as cp
libs = {
 'jdatetime ' + version('jdatetime'): lambda jy: jdatetime.date(jy, 1, 1).togregorian().isoformat(),
 'persiantools ' + version('persiantools'): lambda jy: pj.JalaliDate(jy, 1, 1).to_gregorian().isoformat(),
 'khayyam ' + version('khayyam'): lambda jy: khayyam.JalaliDate(jy, 1, 1).todate().isoformat(),
 'convertdate ' + version('convertdate'): lambda jy: '%04d-%02d-%02d' % cp.to_gregorian(jy, 1, 1),
}
out = {}
for name, f in libs.items():
    bad, err = [], 0
    for r in ref:
        try: v = f(r['jy'])
        except Exception: err += 1; continue
        if v != r['nowruz']: bad.append(dict(jy=r['jy'], got=v, want=r['nowruz'], uncertain=r['uncertain']))
    cert = [b for b in bad if not b['uncertain']]
    near = [b for b in cert if 1300 <= b['jy'] <= 1500]
    out[name] = dict(errors=err, bad=bad)
    rng = (min(b['jy'] for b in cert), max(b['jy'] for b in cert)) if cert else ()
    print(name.ljust(22), 'errors', err, '| wrong certain', len(cert), rng, '| 1300-1500:', ' '.join('%d(%s≠%s)' % (b['jy'], b['got'], b['want']) for b in near) or 'none')
json.dump(out, open(__import__('pathlib').Path(__file__).with_name('results-py.json'), 'w'))
