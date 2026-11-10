"""Read-only HTTP checks for visitor favorites; uses its own session."""
import http.cookiejar
import json
import re
import urllib.error
import urllib.request

BASE = 'http://127.0.0.1:8081'
jar = http.cookiejar.CookieJar()
client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
page = client.open(BASE + '/boutique.php').read().decode()
token = re.search(r'data-csrf="([^"]+)"', page)[1]
pid = int(re.search(r'data-favorite-id="(\d+)"', page)[1])


def post(data):
    request = urllib.request.Request(BASE + '/includes/favorites-api.php', data=json.dumps(data).encode(), headers={'Content-Type':'application/json'})
    try:
        with client.open(request) as response:
            return response.status, json.load(response)
    except urllib.error.HTTPError as error:
        return error.code, json.load(error)


assert post(dict(ids=[pid], csrf='wrong'))[0] == 403
for ids in [['1'], [0], [-1], [4294967296], [pid] * 201]:
    assert post(dict(ids=ids, csrf=token))[0] == 400
print('PASS CSRF and strict server ID validation')
status, data = post(dict(ids=[], csrf=token, render=True))
assert status == 200 and data['ids'] == [] and data['html'] == ''
print('PASS empty list')
status, data = post(dict(ids=[pid, pid, 4294967295], csrf=token, render=True))
assert status == 200 and data['ids'] == [pid] and data['removed'] == 1
assert data['html'].count('class="pcard"') == 1 and 'action="/panier.php"' in data['html']
assert 'class="price"' in data['html'] and 'name="csrf"' in data['html']
print('PASS catalogue validation, duplicates, deleted product, current price and existing cart form')
page = client.open(BASE + '/includes/favoris.php').read().decode()
assert 'Vous n’avez pas encore de favoris.' in page and 'data-favorites-script' in page
assert 'href="/assets/css/favorites.css' in page and 'href="/includes/assets/' not in page
assert page.count('class="ibtn favorites-link"') == 1
print('PASS favorites page, public links and mobile header entry')
for path in ['/assets/js/favorites.js', '/assets/css/favorites.css', '/panier.php']:
    assert client.open(BASE + path).status == 200
product = re.search(r'href="(/produit.php\?p=[^"]+)"', data['html'])[1]
detail = client.open(BASE + product).read().decode()
assert 'class="favorite-media"' in detail
print('PASS product detail heart and assets')
