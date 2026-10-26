import { bootGame } from "../duel/DuelClient.js";
import { KeysLab } from "./keyslab/KeysLab.js";

const options = window.__challengeOptions || {};
const clefUrls = window.__clefUrls || null;

bootGame((duelOptions) => new KeysLab({
  ...(duelOptions || options),
  clefUrls,
}));
