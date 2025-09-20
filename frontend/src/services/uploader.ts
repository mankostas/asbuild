import api from '@/services/api';

export interface UploadOptions {
  chunkSize?: number;
  onProgress?: (percent: number) => void;
  fieldKey?: string;
  sectionKey?: string;
  taskId?: string;
}

export const DEFAULT_CHUNK_SIZE = 1024 * 1024; // 1MB default, backend allows up to 5MB

export async function uploadFile(file: File, options: UploadOptions = {}) {
  const chunkSize = options.chunkSize || DEFAULT_CHUNK_SIZE;
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

  const payload: Record<string, any> = {
    filename: file.name,
    field_key: options.fieldKey,
    section_key: options.sectionKey,
  };
  if (options.taskId != null && options.taskId !== '') {
    payload.task_id = options.taskId;
  }

  const { data } = await api.post(`/uploads/${uploadId}/finalize`, payload);

  return data;
}
