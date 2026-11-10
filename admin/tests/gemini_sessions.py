"""Temporary local PHP process: verify configured session folder and visitor isolation.
No API key, provider calls, or changes to the existing running servers.
"""
import http.cookiejar
import json
import os
from pathlib import Path
import socket
import subprocess
import tempfile
import time
import urllib.request

ROOT=Path(__file__).resolve().parents[2]
with tempfile.TemporaryDirectory(prefix='aurya-session-check-')as temporary:
    directory=Path(temporary).resolve()
    assert directory.is_relative_to(Path(tempfile.gettempdir()).resolve())
    sessions=directory/'sessions'
    env=os.environ.copy()
    env.pop('GEMINI_API_KEY',None)
    env['AURYA_SESSION_DIR']=str(sessions)
    with socket.socket()as probe:
        probe.bind(('127.0.0.1',0));port=probe.getsockname()[1]
    command=['C:\\xampp\\php\\php.exe','-n','-d','extension_dir=C:\\xampp\\php\\ext','-d','extension=pdo_mysql','-d','extension=pdo_sqlite','-d','extension=mbstring','-d','extension=fileinfo','-d',f'upload_tmp_dir={directory}','-d',f'sys_temp_dir={directory}','-S',f'127.0.0.1:{port}','-t',str(ROOT)]
    process=subprocess.Popen(command,env=env,stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL,creationflags=subprocess.CREATE_NO_WINDOW)
    try:
        url=f'http://127.0.0.1:{port}/includes/deco-api.php'
        def visitor():
            return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        a,b=visitor(),visitor()
        for _ in range(40):
            try:
                with a.open(url,timeout=1)as response:first=json.load(response)
                break
            except (OSError,ValueError):time.sleep(.15)
        else:raise RuntimeError('Temporary PHP server failed to start.')
        with a.open(url)as response:again=json.load(response)
        with b.open(url)as response:other=json.load(response)
        assert first['csrf']==again['csrf'] and first['csrf']!=other['csrf']
        assert sessions.is_dir() and len(list(sessions.glob('sess_*')))>=2
        assert not first['configured'] and first['messages']==other['messages']==[]
        print('PASS real PHP session directory override, persistent CSRF and isolated visitors')
    finally:
        process.terminate()
        try:process.wait(timeout=5)
        except subprocess.TimeoutExpired:process.kill();process.wait(timeout=5)
