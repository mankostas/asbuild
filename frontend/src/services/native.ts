interface FileOptions {
  accept?: string;
  capture?: string;
  multiple?: boolean;
}

export async function pickFiles(options: FileOptions = {}): Promise<File[]> {
  if ((window as any).native?.pickFiles) {
    return await (window as any).native.pickFiles(options);
  }
  return new Promise((resolve) => {
    const input = document.createElement('input');
    input.type = 'file';
    if (options.accept) input.accept = options.accept;
    if (options.capture) input.capture = options.capture;
    if (options.multiple) input.multiple = true;
    input.onchange = () => {
      resolve(Array.from(input.files || []));
    };
    input.click();
  });
}

export async function capturePhoto(options: FileOptions = {}) {
  if ((window as any).native?.capturePhoto) {
    return await (window as any).native.capturePhoto(options);
  }
  const files = await pickFiles({
    accept: 'image/*',
    capture: 'environment',
    ...options,
  });
  return files[0];
}
