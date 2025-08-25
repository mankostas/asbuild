import api from '@/services/api';

export interface UploadOptions {
  chunkSize?: number;
  onProgress?: (percent: number) => void;
}

export async function uploadFile(file: File, options: UploadOptions = {}) {
  const chunkSize = options.chunkSize || 1024 * 1024;
  const uploadId = crypto.randomUUID();
  const total = Math.ceil(file.size / chunkSize);

  for (let index = 0; index < total; index++) {
    const start = index * chunkSize;
    const chunk = file.slice(start, start + chunkSize);
    const form = new FormData();
    form.append('upload_id', uploadId);
    form.append('index', index.toString());
    form.append('total', total.toString());
    form.append('filename', file.name);
    form.append('chunk', new File([chunk], file.name));

    let attempts = 0;
    while (attempts < 3) {
      try {
        await api.post('/uploads/chunk', form);
        break;
      } catch (e) {
        attempts++;
        if (attempts >= 3) throw e;
      }
    }

    if (options.onProgress) {
      options.onProgress(Math.round(((index + 1) / total) * 100));
    }
  }

  return uploadId;
}
