from typing import Callable

class ProviderFailover:
    def __init__(self, providers: list[tuple[str, Callable]]):
        self.providers=providers
    def fetch(self, *args, **kwargs):
        errors=[]
        for name, provider in self.providers:
            try:
                result=provider(*args, **kwargs)
                if result:
                    return {'provider':name,'data':result}
                errors.append(f'{name}: empty response')
            except Exception as exc:
                errors.append(f'{name}: {exc}')
        raise RuntimeError('All market-data providers failed: ' + ' | '.join(errors))
