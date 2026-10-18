import Dropzone from '@/components/Dropzone';
import { Anchor, Stack, Text } from '@mantine/core';
import axios from 'axios';
import { useState } from 'react';

/**
 * Attachments of an existing record (issue or test case). New files upload right away to
 * `uploadUrl`; `deleteUrl(file)` returns the URL to delete one. People who cannot edit
 * only get download links.
 */
export default function AttachmentsPanel({ initialFiles = [], uploadUrl, deleteUrl, canEdit }) {
  const [files, setFiles] = useState(initialFiles);

  const upload = selected => {
    const newFiles = selected.filter(file => file.id === undefined);

    if (!newFiles.length) {
      return;
    }

    const formData = new FormData();
    newFiles.forEach(file => formData.append('attachments[]', file));

    axios
      .post(uploadUrl, formData, {
        progress: true,
      })
      .then(({ data }) => setFiles(current => [...current, ...data.files]))
      .catch(() => alert('Failed to upload the files'));
  };

  const remove = index => {
    const file = files[index];

    axios
      .delete(deleteUrl(file))
      .then(() => setFiles(current => current.filter(item => item.id !== file.id)))
      .catch(() => alert('Failed to delete the file'));
  };

  if (canEdit) {
    return (
      <Dropzone
        selected={files}
        onChange={upload}
        remove={remove}
      />
    );
  }

  if (!files.length) {
    return (
      <Text
        c='dimmed'
        size='sm'
      >
        No files attached
      </Text>
    );
  }

  return (
    <Stack gap={4}>
      {files.map(file => (
        <Anchor
          key={file.id}
          href={file.path}
          target='_blank'
          rel='noopener noreferrer'
          size='sm'
        >
          {file.name}
        </Anchor>
      ))}
    </Stack>
  );
}
