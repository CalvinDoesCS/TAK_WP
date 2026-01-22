import { DragEndEvent } from "@dnd-kit/core";

const getPaths = (
  event: DragEndEvent
): {
  name: string;
  originPath: string;
  destinationPath: string;
} => {
  const { active, over } = event;
  const scoped = event.collisions?.find((event) => {
    return event.data?.droppableContainer.data.current.scoped;
  });
  const name = active.data.current?.fileName;
  const originPath = active.data.current?.path;
  const destinationPath =
    (scoped
      ? scoped?.data?.droppableContainer.data.current.path
      : over?.data.current?.path) || originPath;

  return {
    name,
    originPath,
    destinationPath,
  };
};

const getIndex = ({ event }: { event: DragEndEvent }) => {
  const { active, over, collisions } = event;

  if (!over) {
    // Initial index
    return active.data.current?.file.index;
  }

  const scoped = collisions?.find((event) => {
    return event.data?.droppableContainer.data.current.scoped;
  });

  if (scoped) {
    return scoped.data?.droppableContainer.data.current.index;
  }

  // Droppable index
  return event.over?.data.current?.index;
};

const getApp = ({ event }: { event: DragEndEvent }) => {
  const { collisions } = event;

  const scoped = collisions?.find((event) => {
    return event.data?.droppableContainer.data.current.scoped;
  });

  if (scoped) {
    return scoped.data?.droppableContainer.data.current.app;
  }

  return null;
};

const getActionProps = ({ event }: { event: DragEndEvent }) => {
  const { name, originPath, destinationPath } = getPaths(event);

  return {
    name,
    originPath,
    destinationPath,
    index: getIndex({ event }),
    app: getApp({ event }),
  };
};

export { getActionProps };
