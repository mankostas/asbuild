interface StorageImpl {
  getAccessToken: () => string | null;
  setAccessToken: (token: string | null) => void;
  getRefreshToken: () => string | null;
  setRefreshToken: (token: string | null) => void;
}

let storage: StorageImpl = {
  getAccessToken: () => localStorage.getItem('access_token'),
  setAccessToken: (token: string | null) =>
    token
      ? localStorage.setItem('access_token', token)
      : localStorage.removeItem('access_token'),
  getRefreshToken: () => localStorage.getItem('refresh_token'),
  setRefreshToken: (token: string | null) =>
    token
      ? localStorage.setItem('refresh_token', token)
      : localStorage.removeItem('refresh_token'),
};

export function injectStorage(custom: Partial<StorageImpl>) {
  storage = { ...storage, ...custom } as StorageImpl;
}

export function getAccessToken() {
  return storage.getAccessToken() || '';
}

export function getRefreshToken() {
  return storage.getRefreshToken() || '';
}

export function setTokens(access: string, refresh: string) {
  storage.setAccessToken(access);
  storage.setRefreshToken(refresh);
}

export function clearTokens() {
  storage.setAccessToken(null);
  storage.setRefreshToken(null);
}
