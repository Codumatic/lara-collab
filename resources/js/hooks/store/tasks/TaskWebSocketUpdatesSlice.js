import { move, reorder } from "@/utils/reorder";
import { produce } from "immer";

const createTaskWebSocketUpdatesSlice = (set, get) => ({
  addTaskLocally: (task) => {
    return set(produce(state => {
      state.tasks[task.status] = [task, ...(state.tasks[task.status] || [])];
    }));
  },
  updateTaskLocally: (taskId, property, value, relatedData = null) => {
    return set(produce(state => {
      const task = get().findTask(taskId);
      const index = state.tasks[task.status].findIndex((i) => i.id === task.id);

      if (property === 'status' && task.status !== value) {
        const result = move(state.tasks, task.status, value, index, 0);

        state.tasks[task.status] = result[task.status];
        state.tasks[value] = result[value];

        state.tasks[value][0][property] = value;
      } else {
        state.tasks[task.status][index][property] = value;

        // If related data is provided, update those properties as well
        if (relatedData) {
          Object.keys(relatedData).forEach((key) => {
            state.tasks[task.status][index][key] = relatedData[key];
          });
        }
      }
    }));
  },
  removeTaskLocally: (taskId) => {
    return set(produce(state => {
      const task = get().findTask(taskId);

      state.tasks[task.status] = state.tasks[task.status].filter(i => i.id !== task.id);
    }));
  },
  restoreTaskLocally: (groupId, newTask) => {
    return set(produce(state => {
      state.tasks[groupId] = [newTask, ...state.tasks[groupId]].sort((a, b) => (a.order_column > b.order_column ? 1 : -1));
    }));
  },
  addCommentLocally: (comment) => {
    return set(produce(state => {
      state.comments = [comment, ...state.comments];
    }));
  },
  addAttachmentsLocally: (attachments) => {
    return set(produce(state => {
      const task = get().findTask(attachments[0].task_id);
      const index = state.tasks[task.status].findIndex(i => i.id === task.id);

      state.tasks[task.status][index].attachments = [...state.tasks[task.status][index].attachments, ...attachments];
    }));
  },
  removeAttachmentLocally: (taskId, attachmentId) => {
    return set(produce(state => {
      const task = get().findTask(taskId);
      const index = state.tasks[task.status].findIndex(i => i.id === taskId);

      state.tasks[task.status][index].attachments = state.tasks[task.status][index].attachments.filter(i => i.id !== attachmentId);
    }));
  },
  addTimeLogLocally: (timeLog) => {
    return set(produce(state => {
      const task = get().findTask(timeLog.task_id);
      const index = state.tasks[task.status].findIndex(i => i.id === task.id);

      state.tasks[task.status][index].time_logs = [...state.tasks[task.status][index].time_logs, timeLog];
    }));
  },
  removeTimeLogLocally: (taskId, timeLogId) => {
    return set(produce(state => {
      const task = get().findTask(taskId);
      const index = state.tasks[task.status].findIndex(i => i.id === taskId);

      state.tasks[task.status][index].time_logs = state.tasks[task.status][index].time_logs.filter(i => i.id !== timeLogId);
    }));
  },
  reorderTaskLocally: (groupId, fromIndex, toIndex) => {
    const result = reorder(get().tasks[groupId], fromIndex, toIndex);
    return set(produce(state => { state.tasks[groupId] = result }));
  },
  moveTaskLocally: (fromGroupId, toGroupId, fromIndex, toIndex) => {
    const result = move(get().tasks, fromGroupId, toGroupId, fromIndex, toIndex);


    return set(produce(state => {
      state.tasks[fromGroupId] = result[fromGroupId];
      state.tasks[toGroupId] = result[toGroupId];
      state.tasks[toGroupId][toIndex] = {...state.tasks[toGroupId][toIndex], status: toGroupId};
    }));
  },
});

export default createTaskWebSocketUpdatesSlice;
