import { bootGame } from "../duel/DuelClient.js";
import { NoteNest } from "./notenest/NoteNest.js";

const options = window.__challengeOptions || {};
const clefUrls = window.__clefUrls || null;

bootGame((duelOptions) => new NoteNest({
  ...(duelOptions || options),
  clefUrls,
  microphoneSettings: window.__microphoneSettings,
}));
