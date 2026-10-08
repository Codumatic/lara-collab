import { onUploadProgress } from '@/utils/axios';
import axios from 'axios';
import { produce } from "immer";

const createTaskAttachmentsSlice = (set, get) => ({
  uploadAttachments: async (task, files) => {
    const index = get().tasks[task.status].findIndex((i) => i.id === task.id);

    try {
      const { data } = await axios.postForm(
        route("projects.tasks.attachments.upload", [task.project_id, task.id]),
        { attachments: files.filter(i => i.id === undefined) },
        { onUploadProgress }
      );

      return set(produce(state => {
        state.tasks[task.status][index].attachments = [
          ...state.tasks[task.status][index].attachments,
          ...data.files,
        ];
      }));
    } catch (e) {
      console.error(e);
      alert("Failed to upload attachments");
    }
  },
  deleteAttachment: async (task, index) => {
    const taskIndex = get().tasks[task.status].findIndex((i) => i.id === task.id);

    try {
      const deleteId = get().tasks[task.status][taskIndex].attachments[index].id;
      await axios.delete(route("projects.tasks.attachments.destroy", [task.project_id, task.id, deleteId]), { progress: true });

      return set(produce(state => {
        state.tasks[task.status][taskIndex].attachments = [
          ...state.tasks[task.status][taskIndex].attachments.filter(i => i.id !== deleteId)
        ];
      }));
    } catch (e) {
      console.error(e);
      alert("Failed to delete attachment");
    }
  },
});

export default createTaskAttachmentsSlice;
