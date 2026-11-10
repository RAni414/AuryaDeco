"""Compatibility entry point: the chatbot now uses Gemini."""
import pathlib, runpy
runpy.run_path(str(pathlib.Path(__file__).with_name('gemini_http.py')), run_name='__main__')
