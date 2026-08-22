import os
from fastapi import Header, HTTPException

def require_token(authorization: str | None = Header(default=None)):
    expected = os.getenv('AI_SERVICE_TOKEN','')
    if not expected: return
    if authorization != f'Bearer {expected}':
        raise HTTPException(status_code=401, detail='Unauthorized')

import hashlib, hmac, time, os

def sign_callback(body: bytes) -> dict:
    secret = os.getenv('AI_INTERNAL_CALLBACK_SECRET', '')
    if not secret:
        raise RuntimeError('AI_INTERNAL_CALLBACK_SECRET is required for callbacks')
    ts = str(int(time.time()))
    sig = hmac.new(secret.encode(), ts.encode()+b'.'+body, hashlib.sha256).hexdigest()
    return {'X-STETECH-Timestamp': ts, 'X-STETECH-Signature': sig, 'Content-Type': 'application/json'}
