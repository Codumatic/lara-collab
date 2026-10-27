import { router } from '@inertiajs/react';
import { Button, Group, Modal, TextInput, Textarea } from '@mantine/core';
import { useState } from 'react';

/**
 * Small form to add or rename an epic or a story. `request` is { method, url } or null when closed.
 */
export default function TitleModal({ request, onClose }) {
  const [title, setTitle] = useState(request?.title ?? '');
  const [description, setDescription] = useState(request?.description ?? '');
  const [errors, setErrors] = useState({});
  const [saving, setSaving] = useState(false);

  const save = event => {
    event.preventDefault();
    setSaving(true);

    router[request.method](
      request.url,
      { title, description },
      {
        preserveScroll: true,
        onSuccess: onClose,
        onError: setErrors,
        onFinish: () => setSaving(false),
      }
    );
  };

  return (
    <Modal
      opened={!!request}
      onClose={onClose}
      title={request?.heading}
      centered
    >
      <form onSubmit={save}>
        <TextInput
          label='Title'
          required
          data-autofocus
          value={title}
          onChange={event => setTitle(event.target.value)}
          error={errors.title}
        />
        <Textarea
          label='Description'
          mt='md'
          autosize
          minRows={2}
          value={description ?? ''}
          onChange={event => setDescription(event.target.value)}
        />
        <Group
          justify='flex-end'
          mt='lg'
        >
          <Button
            variant='transparent'
            onClick={onClose}
          >
            Cancel
          </Button>
          <Button
            type='submit'
            loading={saving}
          >
            Save
          </Button>
        </Group>
      </form>
    </Modal>
  );
}
