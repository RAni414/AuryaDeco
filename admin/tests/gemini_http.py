"""Local HTTP checks. --live explicitly consumes Gemini quota, never embeds a key."""
import argparse
import http.cookiejar
import json
import pathlib
import urllib.request
import urllib.error
import urllib.parse
import uuid

parser=argparse.ArgumentParser()
parser.add_argument('--base',default='http://127.0.0.1:8081')
parser.add_argument('--live',action='store_true')
args=parser.parse_args()
BASE=args.base.rstrip('/')
if urllib.parse.urlparse(BASE).hostname not in ['127.0.0.1','localhost']:
    raise SystemExit('Local development server only.')
client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
URL=BASE+'/includes/deco-api.php'

def request(fields=None,photo=None):
    if fields is None:
        req=urllib.request.Request(URL)
    else:
        boundary='test-'+uuid.uuid4().hex
        chunks=[]
        for key,value in fields.items():
            chunks.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode())
        if photo is not None:
            chunks.append(f'--{boundary}\r\nContent-Disposition: form-data; name="photo"; filename="test.jpg"\r\nContent-Type: image/jpeg\r\n\r\n'.encode()+photo+b'\r\n')
        chunks.append(f'--{boundary}--\r\n'.encode())
        req=urllib.request.Request(URL,data=b''.join(chunks),headers={'Content-Type':f'multipart/form-data; boundary={boundary}'})
    try:
        with client.open(req,timeout=60)as response:
            return response.status,json.load(response)
    except urllib.error.HTTPError as error:
        return error.code,json.load(error)

status,config=request()
assert status==200 and config['provider']=='gemini' and config['plants']
assert not config['simulation']
token=config['csrf']; plant=config['plants'][0]
fields=dict(csrf=token,consent='1',kind='artificielle',plant=str(plant['id']),variant=str(plant['variant_id']),message='Bonjour, je décore mon salon. Mon budget est 5000 TND.',budget='5000',room='Salon',style='Naturel')
assert request(dict(fields,csrf='wrong'))[0]==403
assert request(dict(csrf=token,action='simulate',consent='1'))[0]==503
print('PASS session, catalogue, CSRF and no paid simulation')
if not config['configured']:
    status,data=request(fields)
    assert status==503 and 'GEMINI_API_KEY' in data['error'] and 'reply' not in data
    assert request(dict(csrf=token,action='reset'))[0]==200
    print('PASS missing key: explicit error, no fabricated advice, reset available')
    print('SKIP live conversation/photo: configure a NEW key through masked PowerShell input.')
    raise SystemExit(0)
assert request(dict(fields,plant='99999999'))[0]==400
assert request(fields,b'not-a-real-image-at-all')[0]==400
assert request(fields,b'x'*(5*1024*1024+1))[0]==400
print('PASS invalid catalogue item and invalid/oversized image (no model call)')
if not args.live:
    print('SKIP actual Gemini calls: use --live after confirming the project is on Free Tier.')
    raise SystemExit(0)
assert request(dict(csrf=token,action='reset'))[0]==200
status,one=request(fields)
assert status==200 and one['reply'] and not one['demo'],one.get('error','Missing real reply')
print('PASS LIVE real Gemini text response')
fields['message']='Analyse cette photo. Propose deux emplacements visibles et recommande la plante sélectionnée si elle convient.'
photo=(pathlib.Path(__file__).resolve().parents[2]/'assets/images/hero-deco-poster.jpg').read_bytes()
status,two=request(fields,photo)
assert status==200 and two['reply'] and two['cards'],two.get('error','No catalogue recommendation returned')
assert all(card['id']==int(plant['id']) for card in two['cards'])
print('PASS LIVE photo response and verified real catalogue recommendation')
_,state=request()
assert len(state['messages'])==4
other=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
with other.open(URL)as response: isolated=json.load(response)
assert isolated['messages']==[]
assert request(dict(csrf=token,action='reset'))[0]==200
_,state=request();assert state['messages']==[]
print('PASS LIVE multi-message session, visitor isolation and new conversation')
