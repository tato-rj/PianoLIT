import { bootGame } from "../duel/DuelClient.js";
import { NoteMatch } from "./notematch/NoteMatch.js";

const options = window.__challengeOptions || {};
const clefUrls = window.__clefUrls || null;

bootGame((duelOptions) => new NoteMatch({
  ...(duelOptions || options),
  clefUrls,
  microphoneSettings: window.__microphoneSettings,
}));
